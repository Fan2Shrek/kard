<?php

declare(strict_types=1);

namespace App\Game\Model;

use App\Enum\GameEventTypeEnum;
use App\Game\Model\Event\GameEvent;
use App\Game\Model\State\GameState;

final class GameContext
{
	/**
	 * @var GameEvent[]
	 */
	private array $events = [];

	public function __construct(
		public GameState $gameState,
	) {
	}

	public function pushEvent(GameEventTypeEnum $type, array $payload = []): void
	{
		$this->events[] = new GameEvent($type, $payload);
	}

	/**
	 * @return GameEvent[]
	 */
	public function flushEvents(): array
	{
		$events = $this->events;
		$this->events = [];

		return $events;
	}

	public function pushScoreUpdate(string $playerId, int $newScore): void
	{
		$this->pushEvent(GameEventTypeEnum::SCORE_UPDATED, [
			'playerId' => $playerId,
			'scores' => $newScore,
		]);
	}
}
