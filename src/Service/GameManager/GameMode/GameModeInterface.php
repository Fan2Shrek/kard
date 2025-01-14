<?php

namespace App\Service\GameManager\GameMode;

use App\Model\Card\Card;
use App\Model\Card\Hand;
use App\Model\GameContext;

interface GameModeInterface
{
    /**
     * @param array<Card> $cards
     */
    public function play(array $cards, GameContext $gameContext): void;

    public function getGameMode(): GameModeEnum;

    /**
     * @param array<string, Hand> $players
     *
     * @return array<string>
     */
    public function getPlayerOrder(array $players): array;

    public function isGameFinished(GameContext $gameContext): bool;
}
