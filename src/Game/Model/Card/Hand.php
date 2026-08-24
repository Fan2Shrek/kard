<?php

namespace App\Game\Model\Card;

final readonly class Hand extends AbstractCardStack
{
	public function has(string $cardId): bool
	{
		return \in_array($cardId, $this->cards, true);
	}

	public function hasCards(array $cardIds): bool
	{
		return empty(array_diff($cardIds, $this->cards));
	}
}
