<?php

namespace App\Model\Card;

final class Deck
{
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
        return array_shift($this->cards);
    }
}
