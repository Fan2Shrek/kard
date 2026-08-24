<?php

namespace App\Game\Model\Card;

use App\Enum\Card\Rank;
use App\Enum\Card\Suit;
use Symfony\Component\Serializer\Attribute\Ignore;

final readonly class Card
{
    public function __construct(
		public string $id,
        public Rank $rank,
        public ?Suit $suit,
    ) {
    }

    #[Ignore]
    public function getImg(): string
    {
        return sprintf('%s%s.svg', $this->rank->value, $this->suit->value ?? '');
    }

    public function isSameAs(Card $card): bool
    {
        return $this->rank->value === $card->rank->value && $this->suit->value === $card->suit->value;
    }

    public function __toString(): string
    {
        return sprintf('%s%s', $this->rank->value, $this->suit->value);
    }
}
