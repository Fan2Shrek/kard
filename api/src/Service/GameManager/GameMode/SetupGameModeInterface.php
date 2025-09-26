<?php

declare(strict_types=1);

namespace App\Service\GameManager\GameMode;

use App\Model\Card\Hand;
use App\Model\GameContext;

interface SetupGameModeInterface extends GameModeInterface
{
    /**
     * Setup the game mode with the given hands.
     *
     * @param array<string, Hand> $hands
     */
    public function setup(GameContext $ctx, array $hands): void;
}
