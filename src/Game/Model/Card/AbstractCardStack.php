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

	public function addCard(string $card): static
	{
		// @phpstan-ignore new.static (subclasses are final and don't override the constructor)
		return new static([...$this->cards, $card]);
	}

	public function removeCard(string $card): static
	{
		// @phpstan-ignore new.static (subclasses are final and don't override the constructor)
		return new static(array_values(array_filter($this->cards, fn (string $c): bool => $c !== $card)));
	}
}
