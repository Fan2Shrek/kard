<?php

declare(strict_types=1);

namespace App\Game\Event;

use App\Entity\Room;
use App\Game\Model\Player;
use Symfony\Component\Serializer\Attribute\Ignore;

final class TurnSkippedEvent extends AbstractGameEvent
{
    public function __construct(
        Room $room,
        public readonly Player $player,
    ) {
        parent::__construct($room);
    }

    #[Ignore]
    public function getType(): string
    {
        return 'turn_skipped';
    }
}
