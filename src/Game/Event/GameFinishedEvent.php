<?php

declare(strict_types=1);

namespace App\Game\Event;

use App\Entity\Room;
use App\Game\Model\State\GameState;

final readonly class GameFinishedEvent
{
    public function __construct(
        public Room $room,
        public GameState $context,
    ) {
    }
}
