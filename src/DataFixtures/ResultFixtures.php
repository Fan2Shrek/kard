<?php

namespace App\DataFixtures;

use App\Entity\GameMode;
use App\Entity\Result;
use App\Entity\User;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class ResultFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    protected function getEntityClass(): string
    {
        return Result::class;
    }

    protected function getData(): iterable
    {
        for ($i = 0; $i < 10; ++$i) {
            yield [
                'winner' => $this->getReference('User_1', User::class),
                'gameMode' => $this->getReference('GameMode_1', GameMode::class),
                'date' => new \DateTimeImmutable('yesterday'),
            ];
        }

        for ($i = 0; $i < 5; ++$i) {
            yield [
                'winner' => $this->getReference('User_2', User::class),
                'gameMode' => $this->getReference('GameMode_1', GameMode::class),
                'date' => new \DateTimeImmutable('yesterday'),
            ];
        }
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            GameModeFixtures::class,
        ];
    }
}
