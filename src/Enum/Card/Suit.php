<?php

namespace App\Enum\Card;

enum Suit: string
{
    case HEARTS = 'h';
    case DIAMONDS = 'd';
    case CLUBS = 'c';
    case SPADES = 's';

    public function getSymbol(): string
    {
        return match ($this) {
            self::HEARTS => '♥️',
            self::DIAMONDS => '♦️',
            self::CLUBS => '♣️',
            self::SPADES => '♠️',
        };
    }
}
