<?php

declare(strict_types=1);

namespace App\Game\Event;

use App\Entity\Room;
use App\Game\Model\State\GameState;
use App\Game\Model\State\PlayerState;

final readonly class GameFinishedEvent
{
    public function __construct(
        public Room $room,
        public GameState $context,
        // a PlayerState, not a User: the winner can be a bot, which has no User row
        public PlayerState $winner,
    ) {
    }
}
