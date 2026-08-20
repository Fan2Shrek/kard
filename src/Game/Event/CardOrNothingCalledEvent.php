<?php

declare(strict_types=1);

namespace App\Game\Event;

use App\Entity\Room;
use App\Enum\Card\Rank;
use App\Game\Model\Player;
use Symfony\Component\Serializer\Attribute\Ignore;

final class CardOrNothingCalledEvent extends AbstractGameEvent
{
    public function __construct(
        Room $room,
        public readonly Player $player,
        public readonly Rank $rank,
        public readonly bool $isCallForFour,
    ) {
        parent::__construct($room);
    }

    #[Ignore]
    public function getType(): string
    {
        return 'card_or_nothing_called';
    }
}
