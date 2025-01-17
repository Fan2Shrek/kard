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

    public function getData(): iterable
    {
        yield [
            'name' => 'President',
            'value' => GameModeEnum::PRESIDENT,
        ];

        yield [
            'name' => 'AMERICAN_EIGHT',
            'value' => GameModeEnum::AMERICAN_EIGHT,
            'active' => false
        ];
    }
}
