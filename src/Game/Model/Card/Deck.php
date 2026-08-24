<?php

namespace App\Game\Model\Card;

final readonly class Deck
{
	/**
	 * @param Card[] $cards
	 */
	public function __construct(
		public array $cards,
	) {
	}

	public function shuffle(): self
	{
		$shuffledCards = $this->cards;
		shuffle($shuffledCards);

		return new self($shuffledCards);
	}
}
