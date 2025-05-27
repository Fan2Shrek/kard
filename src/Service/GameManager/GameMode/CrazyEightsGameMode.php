<?php

declare(strict_types=1);

namespace App\Service\GameManager\GameMode;

use App\Domain\Exception\RuleException;
use App\Enum\Card\Rank;
use App\Model\GameContext;

final class CrazyEightsGameMode extends AbstractGameMode implements SetupGameModeInterface
{
    use CardsHelperTrait;

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

    protected function doPlay(array $cards, GameContext $gameContext): void
    {
        $currentCards = $gameContext->getCurrentCards();
        // always the last card played
        $currentCard = end($currentCards);

        if (!$this->allSameRank($cards) && !$this->allSameSuit($cards)) {
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

        if (Rank::JACK === $mainCard->rank) {
            $gameContext->nextPlayer();
            $this->dispatchMercureEvent(
                'message',
                \sprintf('Le joueur %s saute son tour', $gameContext->getCurrentPlayer()->username),
            );
        }

        $gameContext->setCurrentCards($cards);
        $gameContext->addData('lastPlayer', $gameContext->getCurrentPlayer()->id);
        $gameContext->nextPlayer();
    }
}
