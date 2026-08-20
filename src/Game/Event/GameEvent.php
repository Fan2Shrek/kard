<?php

declare(strict_types=1);

namespace App\Game\Event;

interface GameEvent
{
    public function getType(): string;
}
