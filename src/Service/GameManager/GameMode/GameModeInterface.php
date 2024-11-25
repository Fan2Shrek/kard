<?php

namespace App\Service\GameManager\GameMode;

use App\Model\Card\Card;
use App\Model\GameContext;

interface GameModeInterface
{
    public function play(Card $card, GameContext $gameContext): void;
    public function getGameMode(): GameModeEnum;
}
