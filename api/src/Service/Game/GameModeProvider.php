<?php

declare(strict_types=1);

namespace App\Service\Game;

use App\Entity\Room;
use App\Service\GameManager\GameMode\GameModeInterface;

final class GameModeProvider
{
	public function __construct(
		private iterable $gameModes,
	) {
	}

	public function getForRoom(Room $room): GameModeInterface
	{
		foreach ($this->gameModes as $gameMode) {
			if ($room->getGameMode()->getValue() === $gameMode->getGameMode()) {
				return $gameMode;
			}
		}

		throw new \LogicException();
	}
}
