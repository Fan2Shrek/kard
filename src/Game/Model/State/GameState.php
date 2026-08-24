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
		$rounds = $this->rounds;
		$rounds[$round->roundNumber] = $round;

		return new self(
			$this->players,
			$this->playerOrder,
			$this->currentPlayerId,
			$rounds,
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

	/**
	 * @param array<int, Round> $rounds
	 */
	public function withRounds(array $rounds): self
	{
		return new self(
			$this->players,
			$this->playerOrder,
			$this->currentPlayerId,
			$rounds,
			$this->discardPile,
			$this->drawPile,
			$this->cards,
		);
	}

	public function withUpdatedRound(Round $round): self
	{
		$rounds = $this->rounds;
		$rounds[$round->roundNumber] = $round;

		return new self(
			$this->players,
			$this->playerOrder,
			$this->currentPlayerId,
			$rounds,
			$this->discardPile,
			$this->drawPile,
			$this->cards,
		);
	}

	public function everyoneCanPlay(): bool
	{
		return false;
	}

	public function getPlayerStateById(string $id): PlayerState
	{
		return $this->players[$id] ?? throw new \InvalidArgumentException(\sprintf('Player with ID "%s" not found.', $id));
	}

	public function getCardById(string $cardId): Card
	{
		return $this->cards[$cardId] ?? throw new \InvalidArgumentException(\sprintf('Card with ID "%s" not found.', $cardId));
	}

	public function getCurrentRound(): ?Round
	{
		return $this->rounds[$this->getCurrentRoundNumber()] ?? null;
	}

	public function getCurrentRoundNumber(): int
	{
		return array_key_last($this->rounds) ?? 0;
	}

	public function getNextPlayerId(): string
	{
		$currentIndex = array_search($this->currentPlayerId, $this->playerOrder, true);

		if ($currentIndex === false) {
			throw new \RuntimeException('Current player ID not found in player order.');
		}

		$nextIndex = ($currentIndex + 1) % count($this->playerOrder);

		return $this->playerOrder[$nextIndex];
	}
}
