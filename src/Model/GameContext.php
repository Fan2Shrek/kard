<?php

namespace App\Model;

use App\Entity\Room;
use App\Model\Card\Card;

final readonly class GameContext
{
    public function __construct(
        public Room $room,
        public array $deck,
        public ?Card $currentCard = null,
        public array $discarded = [],
    ) {
    }
}
