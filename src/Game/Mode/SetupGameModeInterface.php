<?php

declare(strict_types=1);

namespace App\Game\Mode;

use App\Game\Model\Card\Hand;
use App\Game\Model\GameState;

interface SetupGameModeInterface extends GameModeInterface
{
    /**
     * Setup the game mode with the given hands.
     *
     * @param array<string, Hand> $hands
     */
    public function setup(GameState &$ctx, array $hands): void;
}
