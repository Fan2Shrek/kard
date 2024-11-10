<?php

namespace App\Model\Card;

use App\Enum\Card\Rank;
use App\Enum\Card\Suit;

final readonly class Card
{
    public function __construct(
        public readonly Suit $suit,
        public readonly Rank $rank,
    ) {
    }
}
