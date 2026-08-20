<?php

declare(strict_types=1);

namespace App\Game\Event;

use App\Entity\Room;
use App\Game\Model\Card\Card;
use App\Game\Model\Player;
use Symfony\Component\Serializer\Attribute\Ignore;

final class CardPlayedEvent extends AbstractGameEvent
{
    /**
     * @param Card[] $cards
     */
    public function __construct(
        Room $room,
        public readonly Player $player,
        public readonly array $cards,
    ) {
        parent::__construct($room);
    }

    #[Ignore]
    public function getType(): string
    {
        return 'card_played';
    }
}
