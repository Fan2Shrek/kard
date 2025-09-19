<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Room;
use App\Model\GameContext;

final class GameFinishedEvent
{
    public function __construct(
        public Room $room,
        public GameContext $context,
    ) {
    }
}
