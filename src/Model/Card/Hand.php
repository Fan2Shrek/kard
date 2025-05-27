<?php

namespace App\Model\Card;

final class Hand implements \Countable
{
    /**
     * @param Card[] $cards
     */
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

    /**
     * @param Card[] $cards
    */
    public function addMultipleCards(array $cards): void
    {
        foreach ($cards as $card) {
            if (!$card instanceof Card) {
                throw new \InvalidArgumentException('All items must be instances of Card');
            }
            $this->add($card);
        }
    }

    public function remove(Card $card): void
    {
        if (!$this->has($card)) {
            throw new \InvalidArgumentException('Card not found in hand');
        }

        $cards = $this->cards;
        foreach ($this->cards as $key => $c) {
            if ($c->isSameAs($card)) {
                unset($cards[$key]);
                $this->cards = array_values($cards);
                break;
            }
        }
    }

    public function count(): int
    {
        return count($this->cards);
    }

    public function has(Card $card): bool
    {
        foreach ($this->cards as $c) {
            if ($c->isSameAs($card)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Card[] $cards
     */
    public function hasCards(array $cards): bool
    {
        foreach ($cards as $card) {
            if ($this->has($card)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Card[] $cards
     */
    public function removeCards(array $cards): void
    {
        foreach ($cards as $card) {
            if ($this->has($card)) {
                $this->remove($card);
            }
        }
    }

    /**
     * @return Card[] $cards
     */
    public function getCards(): array
    {
        return $this->cards;
    }
}
