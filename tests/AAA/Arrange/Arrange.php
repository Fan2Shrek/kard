<?php

declare(strict_types=1);

namespace App\Tests\AAA\Arrange;

use App\Entity\Room;
use App\Enum\Card\Suit;
use App\Enum\Card\Rank;
use App\Model\Card\Card;
use App\Model\GameContext;
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
            new Room(),
            [],
            [$card],
        ));
    }

    public static function setGameStarted(): void
    {
        Act::addContext('gameContext', new GameContext(
            new Room(),
            [],
            [],
        ));
    }
}
