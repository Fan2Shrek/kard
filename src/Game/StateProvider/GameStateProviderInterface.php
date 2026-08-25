<?php

declare(strict_types=1);

namespace App\Game\StateProvider;

use App\Game\Model\State\GameState;

interface GameStateProviderInterface
{
    public function get(string $id): GameState;

    public function save(string $id, GameState $gameState): void;
}
