<?php

declare(strict_types=1);

namespace App\Game\State;

use App\Model\Card\Card;
use App\Model\GameContext;

readonly class GameState
{
	public string $currentPlayerId;

	/**
	 * @param PlayerState[] $players
	 * @param Card[] $cards
	 * @param Card[] $drawPill
	 */
	public function __construct(
		public array $players,
		public int $lastEventId,
		?string $currentPlayerId = null,
		public array $cards = [],
		public array $drawPill = [],
	) {
		$this->currentPlayerId = $currentPlayerId ?? $players[0]->player->id;
	}

	public function getPlayerStateById(string $id): PlayerState
	{
		foreach ($this->players as $state) {
			if ($id === $state->player->id) {
				return $state;
			}
		}

		throw new \LogicException('No player with that id');
	}

	public function withUpdatedPlayer(PlayerState $newState): self
	{
		$newPlayerList = array_map(
			fn (PlayerState $state) => $state->player->id === $newState->player->id ? $newState : $state,
			$this->players,
		);

		return new self(
			$newPlayerList,
			$this->lastEventId,
			$this->currentPlayerId,
			$this->cards,
			$this->drawPill,
		);
	}

	public function withUpdatedDrawPill(array $newDrawPill): self
	{
		return new self(
			$this->players,
			$this->lastEventId,
			$this->currentPlayerId,
			$this->cards,
			$newDrawPill,
		);
	}
}
