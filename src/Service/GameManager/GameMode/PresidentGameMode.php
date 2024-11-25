<?php

namespace App\Service\GameManager\GameMode;

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
        $currentCard = $gameContext->currentCard;
        
        if ($card->rank->value <= $currentCard->rank->value) {
            throw new \Exception('A card with a higher value must be played');
        }
    }
}
