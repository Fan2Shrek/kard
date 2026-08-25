<?php

namespace App\Game\Mode;

use App\Game\Model\GameContext;
use App\Game\Model\State\GameState;
use Symfony\Component\OptionsResolver\OptionsResolver;
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

    public function setup(GameContext $ctx): void
    {
        if ($this->gameMode instanceof SetupGameModeInterface) {
            $this->gameMode->setup($ctx);
        }
    }

    public function play(array $cards, GameContext $context, string $playerId, array $data = []): void
    {
        $event = $this->stopwatch->start('game_mode_play');

        try {
            $this->gameMode->play($cards, $context, $playerId, $data);
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

    public function getPlayerOrder(GameState $state): array
    {
        return $this->gameMode->getPlayerOrder($state);
    }

    public function isGameFinished(GameState $state): bool
    {
        return $this->gameMode->isGameFinished($state);
    }

    public function refreshScore(GameContext $ctx): void
    {
        $event = $this->stopwatch->start('game_mode_refresh_score');

        $this->gameMode->refreshScore($ctx);

        $event->stop();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $this->gameMode->configureOptions($resolver);
    }
}
