<?php

namespace App\Tests\AAA\Act;

use App\Enum\Card\Rank;
use App\Enum\Card\Suit;
use App\Model\Card\Card;

abstract /* static */ class Act
{
    private static array $context = [];

    public static function addContext(string $key, $value): void
    {
        self::$context[$key] = $value;
    }

    public static function playCard(string $value, string $color): void
    {
        $card = new Card(Suit::from($color), Rank::from($value));
        static::get('gamePlayer')->play($card, static::get('gameContext'));
    }

    public static function get(string $key)
    {
        return self::$context[$key] ?? throw new \Exception("Context $key not found");
    }
}
