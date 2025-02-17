<?php

namespace App\Model\Card;

final class Deck
{
    /**
     * @param Card[] $cards
     */
    public function __construct(
        private array $cards,
    ) {
    }

    public function shuffle(): void
    {
        shuffle($this->cards);
    }

    public function draw(): Card
    {
        if (empty($this->cards)) {
            throw new \RuntimeException('Deck is empty');
        }

        return array_shift($this->cards);
    }

    public function count(): int
    {
        return count($this->cards);
    }

    /**
     * @return Card[] $cards
     */
    public function getCards(): array
    {
        return $this->cards;
    }
}
