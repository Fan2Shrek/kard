<?php

namespace App\DataFixtures;

use App\Entity\GameMode;
use App\Entity\GameModeDescription;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

final class GameModeDescriptionFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    protected function getEntityClass(): string
    {
        return GameModeDescription::class;
    }

    protected function getData(): iterable
    {
        yield [
            'gameMode' => $this->getReference('GameMode_1', GameMode::class),
            'img' => 'https://www.president.fr/wp-content/uploads/2020/09/23088734_Cam-EF_DAM_2024-copie-540x540.png',
            'description' => 'Un jeu de fou',
        ];
    }

    public function getDependencies(): array
    {
        return [
            GameModeFixtures::class,
        ];
    }
}
