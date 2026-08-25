<?php

namespace App\Game\Model\Card;

final readonly class Deck
{
    /**
     * @var array<string, Card>
     */
    public array $cards;

    /**
     * @param Card[] $cards
     */
    public function __construct(
        array $cards,
    ) {
        $this->cards = array_combine(array_map(fn (Card $card) => $card->id, $cards), $cards);
    }

    public function shuffle(): self
    {
        $shuffledCards = $this->cards;
        shuffle($shuffledCards);

        return new self($shuffledCards);
    }
}
