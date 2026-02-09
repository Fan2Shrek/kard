<?php

declare(strict_types=1);

namespace App\Service\Game\State;

use App\Entity\Room;
use App\Game\State\GameState;

interface GameStateRepositoryInterface
{
    public function save(GameState $gameState, Room $room): void;

    public function get(Room $room): ?GameState;
}
