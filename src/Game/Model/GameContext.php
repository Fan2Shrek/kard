<?php

declare(strict_types=1);

namespace App\Game\Model;

use App\Game\Event\GameEvent;

final class GameContext
{
    /**
     * @var GameEvent[]
     */
    private array $events = [];

    public function __construct(
        private GameState $state,
    ) {
    }

    public function getState(): GameState
    {
        return $this->state;
    }

    public function replaceState(GameState $state): void
    {
        $this->state = $state;
    }

    public function addEvent(GameEvent $event): void
    {
        $this->events[] = $event;
    }

    /**
     * @return GameEvent[]
     */
    public function flushEvents(): array
    {
        $events = $this->events;
        $this->events = [];

        return $events;
    }
}
