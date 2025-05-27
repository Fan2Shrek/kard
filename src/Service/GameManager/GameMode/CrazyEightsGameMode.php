<?php

declare(strict_types=1);

namespace App\Service\GameManager\GameMode;

use App\Domain\Exception\RuleException;
use App\Enum\Card\Rank;
use App\Model\Card\Hand;
use App\Model\GameContext;
use App\Service\Card\HandRepository;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Serializer\SerializerInterface;

final class CrazyEightsGameMode extends AbstractGameMode implements SetupGameModeInterface
{
    use CardsHelperTrait;

    public function __construct(
        HubInterface $hub,
        private HandRepository $handRepository,
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

    public function setup(GameContext $ctx, array $hands): void
    {
        $ctx->setCurrentCards($ctx->draw(1));
    }

    public function getPlayerOrder(array $hands): array
    {
        $ids = array_keys($hands);
        shuffle($ids);

        return $ids;
    }

    public function isGameFinished(GameContext $gameContext): bool
    {
        foreach ($gameContext->getPlayers() as $player) {
            if (0 === $player->cardsCount) {
                $gameContext->setWinner($player);

                return true;
            }
        }

        return false;
    }

    protected function doPlay(array $cards, GameContext $gameContext, Hand $hand): void
    {
        if (empty($cards)) {
            $hand->add($gameContext->draw(1)[0]);
            $gameContext->nextPlayer();

            return;
        }

        $currentCards = $gameContext->getCurrentCards();
        // always the last card played
        $currentCard = end($currentCards);

        if (!$this->allSameRank($cards)) {
            throw new RuleException($this->getGameMode(), 'Cards are unrelated');
        }

        $mainCard = $cards[0];

        if (!$this->isSameRank($mainCard, $currentCard) && !$this->isSameSuit($mainCard, $currentCard)) {
            throw new RuleException($this->getGameMode(), 'Cannot play this card');
        }

        if (Rank::ACE === $mainCard->rank) {
            $gameContext->setPlayerOrder(array_reverse($gameContext->getPlayers()));
            $this->dispatchMercureEvent(
                'message',
                'Changement de sens !',
            );
        }

        if (Rank::TWO === $mainCard->rank) {
            $nextPlayer = $gameContext->getNextPlayer();
            $nextHand = $this->handRepository->get($nextPlayer->id, $gameContext->getRoom());
            $nextHand->addMultipleCards($gameContext->draw(2 * count($cards)));
            $this->handRepository->save($nextPlayer->id, $gameContext->getRoom(), $nextHand);

            $this->dispatchMercureEvent(
                'message',
                \sprintf('Le joueur %s pioche %d cartes', $gameContext->getNextPlayer()->username, 2 * count($cards)),
            );
            $this->getHub()->publish(new Update(
                sprintf('room-%s-%s', $gameContext->getRoom()->getId(), $nextPlayer->id),
                $this->serializer->serialize($nextHand, 'json'),
            ));

            $gameContext->nextPlayer(); // skip turn
        }

        if (Rank::JACK === $mainCard->rank) {
            $gameContext->nextPlayer();
            $this->dispatchMercureEvent(
                'message',
                \sprintf('Le joueur %s saute son tour', $gameContext->getCurrentPlayer()->username),
            );
        }

        $hand->removeCards($cards);
        $gameContext->setCurrentCards($cards);
        $gameContext->nextPlayer();
    }
}
