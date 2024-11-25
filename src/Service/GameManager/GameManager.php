<?php

namespace App\Service\GameManager;

use App\Entity\Room;
use App\Service\GameManager\GameMode\GameModeEnum;
use App\Service\GameManager\GameMode\GameModeInterface;

final class GameManager
{
    // @param iterable<GameModeInterface> $gameModes
    public function __construct(
        private iterable $gameModes
    ) {
    }

    public function play(Room $room): void
    {

    }

    public function getGameMode(GameModeEnum $gameMode): GameModeInterface
    {
        foreach ($this->gameModes as $gameMode) {
            if ($gameMode->getGameMode() === $gameMode) {
                return $gameMode;
            }
        }

        throw new \InvalidArgumentException('Game mode not found');
    }

    public function getGameModes(): iterable
    {
        return $this->gameModes;
    }
}
