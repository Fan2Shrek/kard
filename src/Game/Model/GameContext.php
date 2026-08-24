<?php

declare(strict_types=1);

namespace App\Game\Model;

use App\Enum\GameEventTypeEnum;
use App\Game\Model\Event\GameEvent;
use App\Game\Model\State\GameState;

class GameContext
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
			'score' => $newScore,
		]);
	}

	public function startNewRound(): void
	{
		$this->pushEvent(GameEventTypeEnum::ROUND_STARTED);
	}

	public function endCurrentRound(): void
	{
		$this->pushEvent(GameEventTypeEnum::ROUND_ENDED);
	}

	/**
	* @param array<string> $cards
	*/
	public function pushTurn(array $cards): void
	{
		$this->pushEvent(GameEventTypeEnum::TURN_PLAYED, [
			'cards' => $cards,
		]);
	}

	public function pushEndTurn(): void
	{
		$this->pushEvent(GameEventTypeEnum::TURN_ENDED);
	}

	public function pushCardDiscarded(string $cardId, string $playerId): void
	{
		$this->pushEvent(GameEventTypeEnum::CARD_DISCARDED, [
			'cardId' => $cardId,
			'playerId' => $playerId,
		]);
	}
}
