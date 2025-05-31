<?php

declare(strict_types=1);

namespace App\Tests\AAA\Arrange;

use App\Entity\Room;
use App\Enum\Card\Rank;
use App\Enum\Card\Suit;
use App\Model\Card\Card;
use App\Model\Card\Hand;
use App\Model\GameContext;
use App\Model\Player;
use App\Model\Turn;
use App\Tests\AAA\Act\Act;
use Ramsey\Uuid\Uuid;

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

    public static function setCurrentHand(array $cards): void
    {
        Act::addContext('handCards', array_map(fn (array $card) => new Card(Suit::from($card[1]), Rank::from((string) $card[0])), $cards));
    }

    public static function setHands(array $hands): void
    {
        Act::addContext('hands', array_reduce(
            array_keys($hands),
            static function (array $carry, int $index) use ($hands): array {
                $carry[$index] = new Hand(array_map(
                    fn (array $card) => new Card(Suit::from($card[1]), Rank::from((string) $card[0])),
                    $hands[$index]
                ));

                return $carry;
            },
            []
        ));
    }

    public static function setDrawPillSize(int $count): void
    {
        $cards = [];

        for ($i = 0; $i < $count; ++$i) {
            $cards[] = new Card(Suit::SPADES, Rank::from((string) ($i + 1)));
        }

        Act::addContext('drawPill', $cards);
        Act::addContext('gameContext', self::createGameContext([]));
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
            new Room(
                Act::get('gameMode'),
                Uuid::uuid4(),
            ),
            [],
            $players,
            current($players),
            $turns,
            Act::get('drawPill') ?? [],
        );
    }
}
