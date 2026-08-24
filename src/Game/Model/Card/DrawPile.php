<?php

declare(strict_types=1);

namespace App\Game\Model\Card;

use Override;

final readonly class DrawPile extends AbstractCardStack
{
	public function getNext(): string
	{
		return array_key_first($this->cards);
	}

	public function removeCard(string $card): static
	{
		$cards = $this->cards;

		unset($cards[$card]);

		return new static($cards);
	}
}
