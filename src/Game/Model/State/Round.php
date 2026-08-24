<?php

declare(strict_types=1);

namespace App\Game\Model\State;

final readonly class Round
{
	public function __construct(
		public array $turns,
	) {
	}

	public function addTurn(Turn $turn): self
	{
		return new self([...$this->turns, $turn]);
	}
}
