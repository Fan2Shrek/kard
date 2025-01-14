<?php

namespace App\Model;

use App\Model\Card\Card;

final class Turn
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

    public function addCard(Card $card): void
    {
        $this->cards[] = $card;
    }
}
