<?php

declare(strict_types=1);

namespace App\Tests\AAA\Arrange;

use App\Entity\Room;
use App\Enum\Card\Suit;
use App\Enum\Card\Rank;
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
        Act::addContext('gameContext', new GameContext(
            'room-id',
            new Room,
            [],
            [],
            new Player('player-id', 'Player 1'),
            [new Turn([$card])],
        ));
    }

    public static function setCurrentCards(array $cards): void
    {
        Act::addContext('gameContext', new GameContext(
            'room-id',
            new Room,
            [],
            [],
            new Player('player-id', 'Player 1'),
            [new Turn(array_map(fn (int $card) => new Card(Suit::SPADES, Rank::from((string) $card)), $cards))],
        ));
    }

    public static function setGameStarted(): void
    {
        Act::addContext('gameContext', new GameContext(
            'room-id',
            new Room,
            [],
            [],
            new Player('player-id', 'Player 1'),
            [],
        ));
    }

    public static function setRound(array $cards): void
    {
        Act::addContext('gameContext', new GameContext(
            'room-id',
            new Room,
            [],
            [],
            new Player('player-id', 'Player 1'),
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
}
