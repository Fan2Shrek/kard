<?php

declare(strict_types=1);

namespace App\Service\Game;

use App\Entity\Room;
use App\Service\GameManager\GameMode\GameModeEnum;
use App\Service\GameManager\GameMode\GameModeInterface;

final class GameModeProvider
{
	public function __construct(
		private iterable $gameModes,
	) {
	}

	public function getForRoom(Room $room): GameModeInterface
	{
		return $this->getForValue($room->getGameMode()->getValue());
	}

	public function getForValue(GameModeEnum $gameMode): GameModeInterface
	{
		foreach ($this->gameModes as $mode) {
			if ($mode->getGameMode() === $gameMode) {
				return $mode;
			}
		}

		throw new \LogicException();
	}
}
