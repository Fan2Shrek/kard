<?php

namespace App\Game\Model\Card;

final readonly class Deck
{
    /**
     * @param Card[] $cards
     */
    public function __construct(
        private array $cards,
    ) {
    }

    public function withShuffled(): self
    {
        $cards = $this->cards;
        shuffle($cards);

        return new self($cards);
    }

    /**
     * @return array{0: self, 1: Card}
     */
    public function withDrawnCard(): array
    {
        if (empty($this->cards)) {
            throw new \RuntimeException('Deck is empty');
        }

        $cards = $this->cards;
        $card = array_shift($cards);

        return [new self($cards), $card];
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
