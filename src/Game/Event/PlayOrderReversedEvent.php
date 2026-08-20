<?php

declare(strict_types=1);

namespace App\Game\Event;

use Symfony\Component\Serializer\Attribute\Ignore;

final class PlayOrderReversedEvent extends AbstractGameEvent
{
    #[Ignore]
    public function getType(): string
    {
        return 'play_order_reversed';
    }
}
