<?php

declare(strict_types=1);

namespace App\Game\State;

use App\Game\Player;
use App\Model\Card\Card;

final readonly class PlayerState
{
	/**
	* @param Card[] $hand
	*/
	public function __construct(
		public Player $player,
		public array $hand,
	) {
	}

	/**
	* @param Card[] $hand
	*/
	public function withNewHand(array $hand): self
	{
		return new self(
			$this->player,
			$hand,
		);
	}
}
