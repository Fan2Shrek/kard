<?php

namespace App\Game\Model;

use App\Game\Model\Card\Card;

final readonly class Turn
{
    /**
     * @param Card[] $cards
     */
    public function __construct(
        private array $cards,
    ) {
    }

    /**
     * @return Card[]
     */
    public function getCards(): array
    {
        return $this->cards;
    }
}
