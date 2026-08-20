<?php

declare(strict_types=1);

namespace App\Game\Mode;

use App\Enum\Card\Rank;
use App\Enum\Card\Suit;
use App\Game\Card\HandRepositoryInterface;
use App\Game\Event\CardDrawnEvent;
use App\Game\Event\CardPlayedEvent;
use App\Game\Event\PlayOrderReversedEvent;
use App\Game\Event\SuitChangedEvent;
use App\Game\Event\TurnSkippedEvent;
use App\Game\Model\Card\Hand;
use App\Game\Model\GameContext;
use App\Game\Model\GameState;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Serializer\SerializerInterface;

final class CrazyEightsGameMode extends AbstractGameMode implements SetupGameModeInterface
{
    use CardsHelperTrait;

    public function __construct(
        HubInterface $hub,
        private HandRepositoryInterface $handRepository,
        private SerializerInterface $serializer,
    ) {
        parent::__construct($hub);
    }

    public function getGameMode(): GameModeEnum
    {
        return GameModeEnum::CRAZY_EIGHTS;
    }

    public function getCardsCount(int $playerCount): int
    {
        return 7;
    }

    public function setup(GameState $ctx, array $hands): void
    {
        $ctx->setCurrentCards($ctx->draw(1));
    }

    public function getPlayerOrder(array $hands): array
    {
        $ids = array_keys($hands);
        shuffle($ids);

        return $ids;
    }

    public function isGameFinished(GameState $gameContext): bool
    {
        foreach ($gameContext->getPlayers() as $player) {
            if (0 === $player->cardsCount) {
                $gameContext->setWinner($player);

                return true;
            }
        }

        return false;
    }

    protected function doPlay(array $cards, GameContext $context, Hand $hand, array $data): void
    {
        $gameContext = $context->getState();

        if (empty($cards)) {
            $player = $gameContext->getCurrentPlayer();

            $hand->addMultipleCards($gameContext->draw(1));
            $context->addEvent(new CardDrawnEvent($gameContext->getRoom(), $player, 1));
            $gameContext->nextPlayer();

            return;
        }

        $actingPlayer = $gameContext->getCurrentPlayer();

        $currentCards = $gameContext->getCurrentCards();
        // always the last card played
        $currentCard = end($currentCards);

        if (!$this->allSameRank($cards)) {
            throw $this->createRuleException('cards.same_rank');
        }

        $mainCard = $cards[0];

        if (Rank::EIGHT === $mainCard->rank) {
            if (!isset($data['name'])) {
                throw new \LogicException('You must provide a name for the new suit');
            }

            $newSuit = Suit::from(strtolower($data['name'][0]));

            $context->addEvent(new SuitChangedEvent($gameContext->getRoom(), $actingPlayer, $newSuit));

            $hand->removeCards($cards);
            $gameContext->addData('suit', $newSuit);
            $gameContext->setCurrentCards($cards);
            $gameContext->addData('lastPlayer', $gameContext->getCurrentPlayer()->id); // @pest-mutate-ignore flemme
            $gameContext->nextPlayer();

            return;
        }

        if (Rank::EIGHT === $currentCard->rank) {
            $suit = $gameContext->getData('suit') ?? $currentCard->suit;
            $suit = $suit instanceof Suit ? $suit : Suit::from($suit); // @pest-mutate-ignore as this is more a denormalization issue

            if ($suit !== $mainCard->suit) {
                throw $this->createRuleException('cards.bad_suit', ['%suit%' => $suit->getSymbol()]);
            }
        }

        if (Rank::EIGHT !== $currentCard->rank && !$this->isSameRank($mainCard, $currentCard) && !$this->isSameSuit($mainCard, $currentCard)) {
            throw $this->createRuleException('cards.same_rank_or_suit', ['%rank%' => $currentCard->rank->value, '%suit%' => $currentCard->suit->getSymbol()]);
        }

        if (Rank::ACE === $mainCard->rank) {
            $gameContext->setPlayerOrder(array_reverse($gameContext->getPlayers()), true);
            $context->addEvent(new PlayOrderReversedEvent($gameContext->getRoom()));

            if (2 === count($gameContext->getPlayers())) {
                $gameContext->nextPlayer();
            }
        }

        if (Rank::TWO === $mainCard->rank) {
            $nextPlayer = $gameContext->getNextPlayer();
            $nextHand = $this->handRepository->get($nextPlayer->id, $gameContext->getRoom());
            $drawnCount = 2 * count($cards);
            $nextHand->addMultipleCards($gameContext->draw($drawnCount));
            $this->handRepository->save($nextPlayer->id, $gameContext->getRoom(), $nextHand); // @pest-mutate-ignore

            $context->addEvent(new CardDrawnEvent($gameContext->getRoom(), $nextPlayer, $drawnCount, true));

            $gameContext->getNextPlayer()->cardsCount += $drawnCount;

            $this->getHub()->publish(new Update(
                sprintf('room-%s-%s', $gameContext->getRoom()->getId(), $nextPlayer->id),
                $this->serializer->serialize($nextHand, 'json'),
            ));

            // todo maybe player can add a 2
            $gameContext->nextPlayer(); // skip turn
        }

        if (Rank::JACK === $mainCard->rank) {
            $gameContext->nextPlayer();
            $context->addEvent(new TurnSkippedEvent($gameContext->getRoom(), $gameContext->getCurrentPlayer()));
        }

        $hand->removeCards($cards);
        $gameContext->setCurrentCards($cards);
        $gameContext->addData('lastPlayer', $actingPlayer->id); // @pest-mutate-ignore flemme
        $gameContext->nextPlayer();

        $context->addEvent(new CardPlayedEvent($gameContext->getRoom(), $actingPlayer, $cards));
    }
}
