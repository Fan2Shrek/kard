<?php

namespace App\Debug;

use App\Model\GameContext;
use App\Service\GameManager\GameMode\GameModeEnum;
use App\Service\GameManager\GameMode\GameModeInterface;
use Symfony\Component\Stopwatch\Stopwatch;

/**
* This is use for debug
*
* Probably unnecessary, but it's a still a good flex :p
*/
final class TraceableGameMode implements GameModeInterface
{
    public function __construct(
        private GameModeInterface $gameMode,
        private Stopwatch $stopwatch,
    ) {}

    public function play(array $cards, GameContext $gameContext): void
    {
        $event = $this->stopwatch->start('game_mode_play');

        try {
            $this->gameMode->play($cards, $gameContext);
        } finally {
            $event->stop();
        }
    }

    public function getGameMode(): GameModeEnum
    {
        return $this->gameMode->getGameMode();
    }

    public function getPlayerOrder(array $players): array
    {
        return $this->gameMode->getPlayerOrder($players);
    }

    public function isGameFinished(GameContext $gameContext): bool
    {
        return $this->gameMode->isGameFinished($gameContext);
    }
}
