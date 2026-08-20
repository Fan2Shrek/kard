<?php

declare(strict_types=1);

namespace App\Game\Event;

use Symfony\Component\Serializer\Attribute\Ignore;

final class RoundEndedEvent extends AbstractGameEvent
{
    #[Ignore]
    public function getType(): string
    {
        return 'round_ended';
    }
}
