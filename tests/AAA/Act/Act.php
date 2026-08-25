<?php

namespace App\Tests\AAA\Act;

use App\Enum\Card\Rank;
use App\Enum\Card\Suit;
use App\Game\Model\Card\Card;
use App\Game\Model\Card\DiscardPile;
use App\Game\Model\Card\DrawPile;
use App\Game\Model\Card\Hand;
use App\Game\Model\Event\GameEvent;
use App\Game\Model\GameContext;
use App\Game\Model\State\GameState;
use App\Game\Model\State\PlayerState;
use App\Game\Service\GameEventApplier;

abstract /* static */ class Act
{
    private static array $context = [];

    /**
     * @var array<string, Card>
     */
    private static array $cardRegistry = [];

    public static function reset(): void
    {
        self::$context = [];
        self::$cardRegistry = [];
    }

    public static function addContext(string $key, $value): void
    {
        self::$context[$key] = $value;
    }

    public static function card(string $rank, string $suit = 's'): Card
    {
        $id = $rank.$suit;

        return self::$cardRegistry[$id] ??= new Card($id, Rank::from($rank), Suit::from($suit));
    }

    /**
     * @return array<string, Card>
     */
    public static function cardRegistry(): array
    {
        return self::$cardRegistry;
    }

    public static function playCard(?string $value, string $color = 's', array $data = []): void
    {
        $ids = $value ? [self::card($value, $color)->id] : [];
        static::play($ids, static::get('gameContext'), $data);
    }

    public static function playCards(array $cards, array $data = []): void
    {
        $ids = array_map(fn (array $card) => self::card((string) $card[0], $card[1] ?? 's')->id, $cards);
        static::play($ids, static::get('gameContext'), $data);
    }

    public static function draw(int $playerCount): int
    {
        return static::get('gamePlayer')->getCardsCount($playerCount);
    }

    public static function setup(): void
    {
        $gameState = static::get('gameContext');
        $context = new GameContext($gameState, static::get('configuration'));

        static::get('gamePlayer')->setup($context);

        self::$context['events'] = $context->flushEvents();
        self::$context['gameContext'] = self::applyEvents($gameState, self::$context['events']);
    }

    /**
     * @param Hand[] $hands
     *
     * @return string[]
     */
    public static function orderPlayers(array $hands): array
    {
        $players = [];
        $i = 1;

        foreach ($hands as $hand) {
            $players[] = new PlayerState((string) $i, "Player {$i}", 0, $hand);
            ++$i;
        }

        $state = new GameState(
            $players,
            array_map(fn (PlayerState $p) => $p->id, $players),
            $players[0]->id,
            [],
            new DiscardPile([]),
            new DrawPile([]),
            self::$cardRegistry,
        );

        return static::get('gamePlayer')->getPlayerOrder($state);
    }

    public static function isGameFinished(): bool
    {
        return static::get('gamePlayer')->isGameFinished(static::get('gameContext'));
    }

    public static function get(string $key): mixed
    {
        return self::$context[$key] ?? null;
    }

    public static function getEvents(): array
    {
        return static::get('events') ?? [];
    }

    /**
     * @param string[] $cardIds
     */
    private static function play(array $cardIds, GameState $gameState, array $data): void
    {
        // $cardIds may include ids only just minted by Act::card() (e.g. the
        // card being played wasn't part of any pre-seeded Arrange fixture) -
        // refresh the state's card registry snapshot so getCardById() sees them.
        $gameState = self::withFreshCardRegistry($gameState);

        $playerId = $gameState->currentPlayerId;

        if (null !== $handCardIds = static::get('handCards')) {
            $hand = new Hand($handCardIds);
        } else {
            $existing = $gameState->getPlayerStateById($playerId)->hand;
            $missing = array_diff($cardIds, $existing->cards);
            $hand = [] === $missing ? $existing : new Hand([...$existing->cards, ...$missing]);
        }

        $gameState = $gameState->withPlayerState(
            $gameState->getPlayerStateById($playerId)->withHand($hand)
        );

        $context = new GameContext($gameState, static::get('configuration'));
        self::$context['events'] = [];

        static::get('gamePlayer')->play($cardIds, $context, $playerId, $data);

        $events = $context->flushEvents();
        self::$context['events'] = $events;

        $gameState = self::applyEvents($gameState, $events);

        self::$context['gameContext'] = $gameState;
        self::$context['currentHand'] = $gameState->getPlayerStateById($playerId)->hand;
    }

    private static function withFreshCardRegistry(GameState $gameState): GameState
    {
        return new GameState(
            $gameState->players,
            $gameState->playerOrder,
            $gameState->currentPlayerId,
            $gameState->rounds,
            $gameState->discardPile,
            $gameState->drawPile,
            self::$cardRegistry,
        );
    }

    /**
     * @param GameEvent[] $events
     */
    private static function applyEvents(GameState $gameState, array $events): GameState
    {
        $applier = new GameEventApplier();

        foreach ($events as $event) {
            $gameState = $applier->apply($event, $gameState);
        }

        return $gameState;
    }
}
