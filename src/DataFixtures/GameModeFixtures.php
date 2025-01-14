<?php

namespace App\DataFixtures;

use App\Entity\GameMode;
use App\Service\GameManager\GameMode\GameModeEnum;

class GameModeFixtures extends AbstractFixtures
{
    public function getEntityClass(): string
    {
        return GameMode::class;
    }

    // TODO : Fix
    public function getData(): iterable
    {
        return [];

        yield [
            'name' => 'President',
            'value' => GameModeEnum::PRESIDENT,
        ];
    }
}
