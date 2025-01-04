<?php

declare(strict_types=1);

namespace App\Tests\AAA\Arrange;

use App\Entity\Room;
use App\Enum\Card\Rank;
use App\Enum\Card\Suit;
use App\Model\Card\Card;
use App\Model\GameContext;
use App\Model\Player;
use App\Model\Turn;
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

        $card = new Card(Suit::from($suit), Rank::from($rank));
        Act::addContext('gameContext', self::createGameContext([new Turn([$card])]));
    }

    public static function setCurrentCards(array $cards): void
    {
        Act::addContext('gameContext', self::createGameContext([new Turn(array_map(fn (int $card) => new Card(Suit::SPADES, Rank::from((string) $card)), $cards))]));
    }

    public static function setGameStarted(): void
    {
        Act::addContext('gameContext', static::createGameContext([]));
    }

    public static function setRound(array $cards): void
    {
        Act::addContext('gameContext', static::createGameContext(
            array_map(
                fn (array $turns) => new Turn(array_map(
                    fn (int $value) => new Card(
                        Suit::SPADES,
                        Rank::from((string) $value)),
                    $turns)),
                $cards
            ),
        ));
    }

    public static function setPlayers(array $players): void
    {
        Act::addContext('gameContextPlayers', $players);
    }

    private static function createGameContext($turns): GameContext
    {
        if (null === $players = Act::get('gameContextPlayers')) {
            $players = [new Player('player-id', 'Player 1')];
        }

        return new GameContext(
            'room-id',
            new Room(),
            [],
            $players,
            current($players),
            $turns,
        );
    }
}
