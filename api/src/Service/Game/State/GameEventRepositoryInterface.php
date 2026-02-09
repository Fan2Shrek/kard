<?php

declare(strict_types=1);

namespace App\Service\Game\State;

use App\Game\State\GameEvent;

interface GameEventRepositoryInterface
{
    public function save(GameEvent $gameEvent): void;

    /**
     * @return GameEvent[]
     */
    public function getEventsSince(?int $lastEventId, string $roomId): array;
}
