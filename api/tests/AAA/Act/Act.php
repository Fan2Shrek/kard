<?php

namespace App\Tests\AAA\Act;

use App\Enum\Card\Rank;
use App\Enum\Card\Suit;
use App\Model\Card\Card;
use App\Model\Card\Hand;
use App\Model\GameContext;

abstract /* static */ class Act
{
    private static array $context = [];

    public static function reset(): void
    {
        self::$context = [];
    }

    public static function addContext(string $key, $value): void
    {
        self::$context[$key] = $value;
    }

    public static function playCard(?string $value, string $color = 's', array $data = []): void
    {
        $play = $value ? [
            self::createCard($value, $color),
        ] : [];
        static::play($play, static::get('gameContext'), static::get('handCards') ?? [], $data);
    }

    public static function playCards(array $cards, array $data = []): void
    {
        $cards = array_map(fn ($card) => self::createCard($card[0], $card[1] ?? 's'), $cards);
        static::play($cards, static::get('gameContext'), static::get('handCards') ?? [], $data);
    }

    public static function draw(int $playerCount): int
    {
        return static::get('gamePlayer')->getCardsCount($playerCount);
    }

    public static function setup(): void
    {
        static::get('gamePlayer')->setup(
            static::get('gameContext'),
            static::get('handCards') ?? []
        );
    }

    public static function orderPlayers(array $hands): array
    {
        return static::get('gamePlayer')->getPlayerOrder($hands);
    }

    public static function isGameFinished(): bool
    {
        return static::get('gamePlayer')->isGameFinished(static::get('gameContext'));
    }

    private static function createCard(string $value, string $color): Card
    {
        return new Card(Suit::from($color), Rank::from($value));
    }

    public static function get(string $key): mixed
    {
        return self::$context[$key] ?? null;
    }

    private static function play(array $cards, GameContext $gameContext, array $handCards, array $data): void
    {
        $currentPlayer = static::get('gameContextPlayers')[current(array_keys(static::get('gameContextPlayers') ?? []))] ?? null;
        $hands = static::get('hands') ?? [];
        $hand = $hands[$currentPlayer?->id] ?? new Hand($handCards);
        self::$context['currentHand'] = $hand;

        static::get('gamePlayer')->play($cards, $gameContext, $hand, $data);
    }
}
