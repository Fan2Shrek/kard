<?php

namespace App\Tests\AAA\Act;

use App\Enum\Card\Rank;
use App\Enum\Card\Suit;
use App\Game\Event\GameEventApplier;
use App\Game\Model\Card\Card;
use App\Game\Model\Card\Hand;
use App\Game\Model\GameContext;
use App\Game\Model\GameState;

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
        $gameContext = static::get('gameContext');
        static::get('gamePlayer')->setup($gameContext, static::get('handCards') ?? []);
        self::$context['gameContext'] = $gameContext;
    }

    public static function orderPlayers(array $hands): array
    {
        return static::get('gamePlayer')->getPlayerOrder($hands);
    }

    public static function isGameFinished(): bool
    {
        $gameContext = static::get('gameContext');
        $result = static::get('gamePlayer')->isGameFinished($gameContext);
        self::$context['gameContext'] = $gameContext;

        return $result;
    }

    private static function createCard(string $value, string $color): Card
    {
        return new Card(Rank::from($value), Suit::from($color));
    }

    public static function get(string $key): mixed
    {
        return self::$context[$key] ?? null;
    }

    public static function getEvents(): array
    {
        return static::get('events') ?? [];
    }

    private static function play(array $cards, GameState $gameState, array $handCards, array $data): void
    {
        $currentPlayer = static::get('gameContextPlayers')[current(array_keys(static::get('gameContextPlayers') ?? []))] ?? null;
        $hands = static::get('hands') ?? [];
        $hand = $hands[$currentPlayer?->id] ?? new Hand($handCards);
        self::$context['currentHand'] = $hand;

        $context = new GameContext($gameState, new GameEventApplier());
        self::$context['events'] = [];
        static::get('gamePlayer')->play($cards, $context, $hand, $data);
        self::$context['events'] = $context->flushEvents();
        self::$context['gameContext'] = $context->getState();
    }
}
