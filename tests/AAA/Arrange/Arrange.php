<?php

declare(strict_types=1);

namespace App\Tests\AAA\Arrange;

use App\Game\Model\Card\DiscardPile;
use App\Game\Model\Card\DrawPile;
use App\Game\Model\Card\Hand;
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
            $ids[] = Act::card((string) ($i + 1), $suits[$i % 4])->id;
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
     * @param array<array{0: string, 1: string}|string> $players tuples of [id, name] or [id, name, score]
     */
    public static function setPlayers(array $players): void
    {
        Act::addContext('gameContextPlayers', $players);
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

        $hands = Act::get('hands') ?? [];

        $players = array_map(
            fn (array $s, int $index) => new PlayerState($s[0], $s[1], $s[2] ?? 0, $hands[$index] ?? new Hand([])),
            $specs,
            array_keys($specs),
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
