<?php

declare(strict_types=1);

namespace App\Game\Event;

use App\Game\Model\GameState;

final readonly class ApplyResult
{
    public function __construct(
        public GameState $state,
        public mixed $output = null,
    ) {
    }
}
