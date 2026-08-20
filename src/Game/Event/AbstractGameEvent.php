<?php

declare(strict_types=1);

namespace App\Game\Event;

use App\Entity\Room;
use Symfony\Component\Serializer\Attribute\Ignore;

abstract class AbstractGameEvent implements GameEvent
{
    public function __construct(
        #[Ignore]
        public readonly Room $room,
    ) {
    }
}
