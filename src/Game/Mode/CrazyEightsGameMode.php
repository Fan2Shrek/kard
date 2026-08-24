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

final class CrazyEightsGameMode extends AbstractGameMode implements SetupGameModeInterface
{
    use CardsHelperTrait;

    public function __construct(
        private HandRepositoryInterface $handRepository,
    ) {
    }

    public function getGameMode(): GameModeEnum
    {
        return GameModeEnum::CRAZY_EIGHTS;
    }

    public function getCardsCount(int $playerCount): int
    {
        return 7;
    }

    public function setup(GameState &$ctx, array $hands): void
    {
        [$ctx, $cards] = $ctx->withDrawnCards(1);
        $ctx = $ctx->withCurrentCards($cards);
    }

    public function getPlayerOrder(array $hands): array
    {
        $ids = array_keys($hands);
        shuffle($ids);

        return $ids;
    }

    public function isGameFinished(GameState &$gameContext): bool
    {
        foreach ($gameContext->getPlayers() as $player) {
            if (0 === $player->cardsCount) {
                $gameContext = $gameContext->withWinner($player);

                return true;
            }
        }

        return false;
    }

    protected function doPlay(array $cards, GameContext $context, Hand $hand, array $data): GameState
    {
        $gameContext = $context->getState();

        if (empty($cards)) {
            $player = $gameContext->getCurrentPlayer();

            $drawnCards = $context->dispatch(new CardDrawnEvent($gameContext->getRoom(), $player, 1));
            $hand->addMultipleCards($drawnCards);

            return $context->mutate(fn (GameState $s): GameState => $s->withNextPlayer());
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

            $context->dispatch(new SuitChangedEvent($gameContext->getRoom(), $actingPlayer, $newSuit));

            $hand->removeCards($cards);

            return $context->mutate(fn (GameState $s): GameState => $s
                ->withCurrentCards($cards)
                ->withLastPlayer($actingPlayer->id) // @pest-mutate-ignore flemme
                ->withNextPlayer());
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
            $context->dispatch(new PlayOrderReversedEvent($gameContext->getRoom()));
            $gameContext = $context->getState();

            if (2 === count($gameContext->getPlayers())) {
                $gameContext = $context->mutate(fn (GameState $s): GameState => $s->withNextPlayer());
            }
        }

        if (Rank::TWO === $mainCard->rank) {
            $nextPlayer = $gameContext->getNextPlayer();
            $nextHand = $this->handRepository->get($nextPlayer->id, $gameContext->getRoom());
            $drawnCount = 2 * count($cards);

            $drawnCards = $context->dispatch(new CardDrawnEvent($gameContext->getRoom(), $nextPlayer, $drawnCount, true));
            $gameContext = $context->getState();
            $nextHand->addMultipleCards($drawnCards);
            $this->handRepository->save($nextPlayer->id, $gameContext->getRoom(), $nextHand); // @pest-mutate-ignore

            // todo maybe player can add a 2
            $gameContext = $context->mutate(fn (GameState $s): GameState => $s->withNextPlayer()); // skip turn
        }

        if (Rank::JACK === $mainCard->rank) {
            $gameContext = $context->mutate(fn (GameState $s): GameState => $s->withNextPlayer());
            $context->dispatch(new TurnSkippedEvent($gameContext->getRoom(), $gameContext->getCurrentPlayer()));
        }

        $hand->removeCards($cards);

        $gameContext = $context->mutate(fn (GameState $s): GameState => $s
            ->withCurrentCards($cards)
            ->withLastPlayer($actingPlayer->id) // @pest-mutate-ignore flemme
            ->withNextPlayer());

        $context->dispatch(new CardPlayedEvent($gameContext->getRoom(), $actingPlayer, $cards));

        return $context->getState();
    }
}
