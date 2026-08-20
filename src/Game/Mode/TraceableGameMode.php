<?php

namespace App\Game\Mode;

use App\Game\Model\Card\Hand;
use App\Game\Model\GameContext;
use App\Game\Model\GameState;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * This is use for debug.
 *
 * Probably unnecessary, but it's a still a good flex :p
 */
final class TraceableGameMode implements GameModeInterface, SetupGameModeInterface
{
    public function __construct(
        private GameModeInterface $gameMode,
        private Stopwatch $stopwatch,
    ) {
    }

    public function setup(GameState $gameContext, array $hands): void
    {
        if ($this->gameMode instanceof SetupGameModeInterface) {
            $this->gameMode->setup($gameContext, $hands);
        }
    }

    public function play(array $cards, GameContext $context, Hand $hand, array $data = []): void
    {
        $event = $this->stopwatch->start('game_mode_play');

        try {
            $this->gameMode->play($cards, $context, $hand, $data);
        } finally {
            $event->stop();
        }
    }

    public function getGameMode(): GameModeEnum
    {
        return $this->gameMode->getGameMode();
    }

    public function getCardsCount(int $playerCount): ?int
    {
        $event = $this->stopwatch->start('game_mode_get_cards_count');

        try {
            return $this->gameMode->getCardsCount($playerCount);
        } finally {
            $event->stop();
        }
    }

    public function getPlayerOrder(array $players): array
    {
        return $this->gameMode->getPlayerOrder($players);
    }

    public function isGameFinished(GameState $gameContext): bool
    {
        return $this->gameMode->isGameFinished($gameContext);
    }
}
