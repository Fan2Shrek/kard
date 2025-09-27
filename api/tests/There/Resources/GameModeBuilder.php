<?php

declare(strict_types=1);

namespace App\Tests\There\Resources;

use App\Entity\GameMode;
use App\Service\GameManager\GameMode\GameModeEnum;

/**
 * @extends AbstractBuilder<GameMode>
 */
final class GameModeBuilder extends AbstractBuilder
{
    public function __construct($container)
    {
        parent::__construct($container, GameMode::class);
    }

    protected function getParams(): array
    {
        return [
            GameModeEnum::PRESIDENT,
        ];
    }
}
