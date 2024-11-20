<?php

namespace App\Model\Card;

final class Hand implements \Countable
{
    public function __construct(
        private array $cards = [],
    ) {
    }

    public function add(Card $card): void
    {
        $this->cards[] = $card;
    }

    public function addCard(Card $card): void
    {
        $this->add($card);
    }

    public function remove(Card $card): void
    {
        $key = array_search($card, $this->cards, true);

        if ($key === false) {
            throw new \InvalidArgumentException('Card not found in hand');
        }

        unset($this->cards[$key]);
    }

    public function count(): int
    {
        return count($this->cards);
    }

    public function has(Card $card): bool
    {
        return in_array($card, $this->cards, true);
    }

    public function getCards(): array
    {
        return $this->cards;
    }
}
