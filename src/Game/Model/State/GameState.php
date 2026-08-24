<?php

declare(strict_types=1);

namespace App\Game\Model\State;

use App\Game\Model\Card\Card;
use App\Game\Model\Card\DiscardPile;
use App\Game\Model\Card\DrawPile;

final readonly class GameState
{
	/**
	 * @var array<string, PlayerState>
	 */
	public array $players;

	/**
	 * @param PlayerState[] $players
	 * @param string[] $playerOrder
	 * @param array<int, Round> $rounds
	 * @param array<string, Card> $cards
	 */
	public function __construct(
		array $players,
		public array $playerOrder,
		public string $currentPlayerId,
		public array $rounds,
		public DiscardPile $discardPile,
		public DrawPile $drawPile,
		public array $cards,
	) {
		$this->players = array_combine(array_map(fn (PlayerState $player) => $player->id, $players), $players);
	}

	public function withCurrentPlayer(string $playerId): self
	{
		return new self(
			$this->players,
			$this->playerOrder,
			$playerId,
			$this->rounds,
			$this->discardPile,
			$this->drawPile,
			$this->cards,
		);
	}

	public function addRound(Round $round): self
	{
		return new self(
			$this->players,
			$this->playerOrder,
			$this->currentPlayerId,
			[...$this->rounds, $round],
			$this->discardPile,
			$this->drawPile,
			$this->cards,
		);
	}

	public function withPlayerState(PlayerState $playerState): self
	{
		return new self(
			[...$this->players, $playerState],
			$this->playerOrder,
			$this->currentPlayerId,
			$this->rounds,
			$this->discardPile,
			$this->drawPile,
			$this->cards,
		);
	}

	public function withDiscardPile(DiscardPile $discardPile): self
	{
		return new self(
			$this->players,
			$this->playerOrder,
			$this->currentPlayerId,
			$this->rounds,
			$discardPile,
			$this->drawPile,
			$this->cards,
		);
	}

	public function withDrawPile(DrawPile $drawPile): self
	{
		return new self(
			$this->players,
			$this->playerOrder,
			$this->currentPlayerId,
			$this->rounds,
			$this->discardPile,
			$drawPile,
			$this->cards,
		);
	}

	public function everyoneCanPlay(): bool
	{
		return false;
	}
}
