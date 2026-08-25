<?php

declare(strict_types=1);

namespace App\Tests\AAA\Arrange;

use App\Game\Model\Card\DiscardPile;
use App\Game\Model\Card\DrawPile;
use App\Game\Model\Card\Hand;
use App\Game\Model\GameConfiguration;
use App\Game\Model\State\GameState;
use App\Game\Model\State\PlayerState;
use App\Game\Model\State\Round;
use App\Game\Model\State\Turn;
use App\Tests\AAA\Act\Act;

abstract /* static */ class Arrange
{
    public static function set()
    {
        // Arrange
    }

    public static function setCurrentCard(string|int $rank = 2, string $suit = 's'): void
    {
        if (is_int($rank)) {
            $rank = (string) $rank;
        }

        $cardId = Act::card($rank, $suit)->id;
        Act::addContext('gameContext', self::createGameState([new Turn('other', [$cardId])]));
    }

    public static function setCurrentCards(array $cards): void
    {
        Act::addContext('gameContext', self::createGameState([new Turn('other', self::cardIdsForRanks($cards))]));
    }

    public static function setCurrentHand(array $cards): void
    {
        Act::addContext('handCards', array_map(
            fn (array $card) => Act::card((string) $card[0], $card[1])->id,
            $cards
        ));
    }

    public static function setHands(array $hands): void
    {
        Act::addContext('hands', array_map(
            fn (array $cards) => new Hand(array_map(
                fn (array $card) => Act::card((string) $card[0], $card[1])->id,
                $cards
            )),
            $hands
        ));
    }

    public static function setDrawPillSize(int $count): void
    {
        $suits = ['s', 'h', 'd', 'c'];
        $ids = [];

        for ($i = 0; $i < $count; ++$i) {
            // DrawPile is keyed by card id (getNext()/removeCard() rely on it)
            $id = Act::card((string) ($i + 1), $suits[$i % 4])->id;
            $ids[$id] = $id;
        }

        Act::addContext('drawPill', $ids);
        Act::addContext('gameContext', self::createGameState([]));
    }

    public static function setGameStarted(): void
    {
        Act::addContext('gameContext', static::createGameState([]));
    }

    public static function setRound(array $cards): void
    {
        Act::addContext('gameContext', static::createGameState(
            array_map(
                fn (array $turnRanks) => new Turn('other', self::cardIdsForRanks($turnRanks)),
                $cards
            ),
        ));
    }

    /**
     * Like setRound(), but each turn also carries the rank it declared (Menteur) -
     * needed to arrange a round a challenge can be resolved against. Turns are
     * attributed to 'player2-id' rather than 'other' so a challenge can actually
     * give them the pile (CARD_GIVEN requires a player registered in the state).
     *
     * @param array<array{cards: array<int, int|string>, rank: int|string}> $turns
     */
    public static function setMenteurRound(array $turns): void
    {
        Act::addContext('gameContext', static::createGameState(
            array_map(
                fn (array $turn) => new Turn('player2-id', self::cardIdsForRanks($turn['cards']), ['rank' => (string) $turn['rank']]),
                $turns,
            ),
        ));
    }

    /**
     * @param array<array{0: string, 1: string}|string> $players tuples of [id, name] or [id, name, score]
     */
    public static function setPlayers(array $players): void
    {
        Act::addContext('gameContextPlayers', $players);
    }

    /**
     * @param array<string, mixed> $options
     */
    public static function setConfiguration(array $options): void
    {
        Act::addContext('configuration', new GameConfiguration($options));
    }

    /**
     * @param array<int, int|string> $ranks
     *
     * @return string[]
     */
    private static function cardIdsForRanks(array $ranks): array
    {
        $suits = ['s', 'h', 'd', 'c'];

        return array_values(array_map(
            fn (int $i, int|string $rank) => Act::card((string) $rank, $suits[$i % 4])->id,
            array_keys($ranks),
            $ranks
        ));
    }

    /**
     * @param Turn[] $turns
     */
    private static function createGameState(array $turns): GameState
    {
        $specs = Act::get('gameContextPlayers') ?? [
            ['player-id', 'Player 1'],
            ['player2-id', 'Player 2'],
            ['player3-id', 'Player 3'],
        ];

        // keyed by player id (matches how setHands() callers key by id, e.g. '1' => [...])
        $hands = Act::get('hands') ?? [];

        $players = array_map(
            fn (array $s) => new PlayerState($s[0], $s[1], $s[2] ?? 0, $hands[$s[0]] ?? new Hand([])),
            $specs,
        );

        // always a round, even with zero turns - a "no round at all" state
        // isn't reachable in the real game and PresidentGameMode requires one
        $rounds = [0 => new Round(0, $turns)];

        return new GameState(
            $players,
            array_map(fn (PlayerState $p) => $p->id, $players),
            $players[0]->id,
            $rounds,
            new DiscardPile([]),
            new DrawPile(Act::get('drawPill') ?? []),
            Act::cardRegistry(),
        );
    }
}
