<?php

namespace App\Service\GameManager\GameMode;

use App\Model\Card\Card;
use App\Model\GameContext;
use App\Model\Player;

interface GameModeInterface
{
    /**
     * @param array<Card> $cards
     */
    public function play(array $cards, GameContext $gameContext): void;

    public function getGameMode(): GameModeEnum;

    /**
     * @param array<Player> $players
     */
    public function getPlayerOrder(array $players): array;
}
