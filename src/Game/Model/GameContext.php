<?php

declare(strict_types=1);

namespace App\Game\Model;

use App\Game\Event\GameEvent;
use App\Game\Event\GameEventApplierInterface;

final class GameContext
{
    /**
     * @var GameEvent[]
     */
    private array $events = [];

    public function __construct(
        private GameState $state,
        private readonly GameEventApplierInterface $applier,
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

    /**
     * Applies a local (non-event) state change and keeps it as the single
     * source of truth, so it's never lost by a later dispatch() reading a
     * stale $this->state.
     *
     * @param callable(GameState): GameState $mutator
     */
    public function mutate(callable $mutator): GameState
    {
        $this->state = $mutator($this->state);

        return $this->state;
    }

    public function addEvent(GameEvent $event): void
    {
        $this->events[] = $event;
    }

    /**
     * Records the event and immediately applies its GameState mutation.
     */
    public function dispatch(GameEvent $event): mixed
    {
        $this->events[] = $event;

        $result = $this->applier->apply($event, $this->state);
        $this->state = $result->state;

        return $result->output;
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
