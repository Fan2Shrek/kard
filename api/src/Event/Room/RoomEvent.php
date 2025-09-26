<?php

declare(strict_types=1);

namespace App\Event\Room;

use App\Entity\Room;

final class RoomEvent
{
    public function __construct(
        public readonly Room $room,
    ) {
    }
}
