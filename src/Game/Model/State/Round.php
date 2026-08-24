<?php

declare(strict_types=1);

namespace App\Game\Model\State;

final readonly class Round
{
	/**
	* @param Turn[] $turns
	*/
	public function __construct(
		public int $roundNumber,
		public array $turns,
	) {
	}

	public function addTurn(Turn $turn): self
	{
		return new self($this->roundNumber, [...$this->turns, $turn]);
	}

	public function isNew(): bool
	{
		return [] === $this->turns;
	}
}
