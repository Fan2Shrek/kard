<?php

namespace App\DataFixtures;

use App\Entity\Result;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class ResultFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    public function getEntityClass(): string
    {
        return Result::class;
    }

    // TODO : Fix
    public function getData(): iterable
    {
        return [];

        for ($i = 0; $i < 10; ++$i) {
            yield [
                'winner' => $this->getReference('User_1'),
                'gameMode' => $this->getReference('GameMode_1'),
                'date' => new \DateTimeImmutable('yesterday'),
            ];
        }

        for ($i = 0; $i < 5; ++$i) {
            yield [
                'winner' => $this->getReference('User_2'),
                'gameMode' => $this->getReference('GameMode_1'),
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
