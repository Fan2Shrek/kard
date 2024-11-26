<?php

namespace App\Service\GameManager\GameMode;

use App\Domain\Exception\RuleException;
use App\Model\Card\Card;
use App\Model\GameContext;

final class PresidentGameMode implements GameModeInterface
{
    public function getGameMode(): GameModeEnum
    {
        return GameModeEnum::PRESIDENT;
    }

    public function play(Card $card, GameContext $gameContext): void
    {
        $currentCards = $gameContext->getCurrentCards();
        
        match (\count($currentCards)) {
            1 => $this->handleOneCard($card, $currentCards[0]),
            default => throw new RuleException($this->getGameMode(), 'Incorrect number of cards played'),
        };
    }

    private function handleOneCard(Card $card, Card $currentCard): void
    {
        if ($card->rank->value < $currentCard->rank->value) {
            throw new RuleException($this->getGameMode(), 'A card with a higher value must be played');
        }
    }
}
