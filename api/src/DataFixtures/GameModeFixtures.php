<?php

namespace App\DataFixtures;

use App\Entity\GameMode;
use App\Service\GameManager\GameMode\GameModeEnum;

class GameModeFixtures extends AbstractFixtures
{
    protected function getEntityClass(): string
    {
        return GameMode::class;
    }

    protected function getData(): iterable
    {
        yield [
            'name' => 'President',
            'value' => GameModeEnum::PRESIDENT,
        ];

        yield [
            'name' => 'Crazy Eights',
            'value' => GameModeEnum::CRAZY_EIGHTS,
            'active' => false,
        ];
    }
}
