<?php

declare(strict_types=1);

namespace App\Game\Service;

use App\Game\Model\Event\GameEvent;

final class GameEventApplier
{
	public function apply(GameEvent $event, GameState $gameState): GameState
	{
		return $gameState;
	}
}
