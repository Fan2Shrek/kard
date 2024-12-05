<?php

namespace App\Service\GameManager\GameMode;

use App\Domain\Exception\RuleException;
use App\Model\Card\Card;
use App\Model\GameContext;

final class PresidentGameMode implements GameModeInterface
{
    use CardsHelperTrait;

    private GameContext $gameContext;

    public function getGameMode(): GameModeEnum
    {
        return GameModeEnum::PRESIDENT;
    }

    public function play(array $cards, GameContext $gameContext): void
    {
        $this->gameContext = $gameContext;
        $currentCards = $gameContext->getCurrentCards();

        match (\count($currentCards)) {
            0 => $this->handleStart($cards),
            1 => $this->handleOneCard($cards[0], $currentCards[0]),
            default => throw new RuleException($this->getGameMode(), 'Incorrect number of cards played'),
        };
    }

    private function handleOneCard(Card $card, Card $currentCard): void
    {
        if ($card->rank->value < $currentCard->rank->value) {
            throw new RuleException($this->getGameMode(), 'A card with a higher value must be played');
        }
    }

    private function handleStart(array $cards): void
    {
        if (!$this->allSameRank($cards)) {
            throw new RuleException($this->getGameMode(), "Can't play multiple cards with different values");
        }
    }
}
