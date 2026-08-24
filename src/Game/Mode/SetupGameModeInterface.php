<?php

declare(strict_types=1);

namespace App\Game\Mode;

use App\Game\Model\GameContext;

interface SetupGameModeInterface extends GameModeInterface
{
    /**
     * Setup the game mode with the given state.
     */
    public function setup(GameContext $ctx): void;
}
