<?php

declare(strict_types=1);

namespace App\Game\Event;

use App\Game\Model\GameState;

interface GameEventApplierInterface
{
    public function apply(GameEvent $event, GameState $state): ApplyResult;
}
