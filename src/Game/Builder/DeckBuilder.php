<?php

declare(strict_types=1);

namespace App\Game\Builder;

use App\Enum\Card\Rank;
use App\Enum\Card\Suit;
use App\Game\Model\Card\Card;
use App\Game\Model\Card\Deck;
use Ramsey\Uuid\Uuid;

final class DeckBuilder
{
	private array $cards = [];

	public function build(): Deck
	{
		foreach (Rank::valueCases() as $rank) {
			foreach (Suit::cases() as $suit) {
				$this->cards[] = new Card(Uuid::uuid4()->toString(), $rank, $suit);
			}
		}

		return new Deck($this->cards);
	}

	public function withJokers(): self
	{
		$this->cards[] = new Card(Uuid::uuid4()->toString(), Rank::JOKER, null);
		$this->cards[] = new Card(Uuid::uuid4()->toString(), Rank::JOKER, null);

		return $this;
	}
}
