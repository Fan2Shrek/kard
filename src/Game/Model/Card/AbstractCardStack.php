<?php

declare(strict_types=1);

namespace App\Game\Model\Card;

abstract readonly class AbstractCardStack
{
    /**
     * @param string[] $cards
     */
    public function __construct(
        public array $cards = [],
    ) {
    }

	public function count(): int
	{
		return count($this->cards);
	}

	public function addCard(Card $card): self
	{
		return new static([...$this->cards, $card]);
	}

	public function removeCard(Card $card): self
	{
		return new static(array_values(array_filter($this->cards, fn (Card $c): bool => !$c->isSameAs($card))));
	}
}
