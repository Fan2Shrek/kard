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

    public static function playCard(string $value, string $color = 's'): void
    {
        $card = self::createCard($value, $color);
        static::get('gamePlayer')->play([$card], static::get('gameContext'));
    }

    public static function playCards(array $cards): void
    {
        $cards = array_map(fn ($card) => self::createCard($card[0], $card[1]), $cards);
        static::get('gamePlayer')->play($cards, static::get('gameContext'));
    }

    private static function createCard(string $value, string $color): Card
    {
        return new Card(Suit::from($color), Rank::from($value));
    }

    public static function get(string $key)
    {
        return self::$context[$key] ?? throw new \Exception("Context $key not found");
    }
}
