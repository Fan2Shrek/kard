<?php

declare(strict_types=1);

namespace App\Game\Event;

use App\Entity\Room;
use App\Enum\Card\Suit;
use App\Game\Model\Player;
use Symfony\Component\Serializer\Attribute\Ignore;

final class SuitChangedEvent extends AbstractGameEvent
{
    public function __construct(
        Room $room,
        public readonly Player $player,
        public readonly Suit $suit,
    ) {
        parent::__construct($room);
    }

    #[Ignore]
    public function getType(): string
    {
        return 'suit_changed';
    }
}
