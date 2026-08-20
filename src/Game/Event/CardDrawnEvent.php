<?php

declare(strict_types=1);

namespace App\Game\Event;

use App\Entity\Room;
use App\Game\Model\Player;
use Symfony\Component\Serializer\Attribute\Ignore;

final class CardDrawnEvent extends AbstractGameEvent
{
    public function __construct(
        Room $room,
        public readonly Player $player,
        public readonly int $count,
        public readonly bool $forced = false,
    ) {
        parent::__construct($room);
    }

    #[Ignore]
    public function getType(): string
    {
        return 'card_drawn';
    }
}
