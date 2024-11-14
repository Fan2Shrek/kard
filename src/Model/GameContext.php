<?php

namespace App\Model;

use App\Entity\Room;

final readonly class GameContext
{
    public function __construct(
        public Room $room,
        public array $deck,
    ) {
    }
}
