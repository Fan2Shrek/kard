<?php

namespace App\DataFixtures;

use App\Entity\GameMode;
use App\Entity\Result;
use App\Entity\Room;
use App\Entity\User;
use App\Enum\GameStatusEnum;
use App\Service\GameManager\GameMode\GameModeEnum;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ResultFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    private ObjectManager $manager;

    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;
        parent::load($manager);
    }

    protected function getEntityClass(): string
    {
        return Result::class;
    }

    protected function getData(): iterable
    {
        yield [
            'winner' => $this->getReference('User_1', User::class), // admin
            'room' => $this->createAndPersistRoom(new GameMode(GameModeEnum::PRESIDENT), $this->getReference('User_1', User::class)),
        ];

        yield [
            'winner' => $this->getReference('User_2', User::class), // user
            'room' => $this->createAndPersistRoom(new GameMode(GameModeEnum::CRAZY_EIGHTS), $this->getReference('User_2', User::class)),
        ];

        yield [
            'winner' => $this->getReference('User_3', User::class), // aaa
            'room' => $this->createAndPersistRoom(new GameMode(GameModeEnum::PRESIDENT), $this->getReference('User_3', User::class)),
        ];

        yield [
            'winner' => $this->getReference('User_1', User::class), // admin
            'room' => $this->createAndPersistRoom(new GameMode(GameModeEnum::CRAZY_EIGHTS), $this->getReference('User_1', User::class)),
        ];

        yield [
            'winner' => $this->getReference('User_2', User::class), // user
            'room' => $this->createAndPersistRoom(new GameMode(GameModeEnum::PRESIDENT), $this->getReference('User_2', User::class)),
        ];
    }

    private function createAndPersistRoom(GameMode $gameMode, User $owner): Room
    {
        $this->manager->persist($gameMode);

        $room = new Room($gameMode, null, GameStatusEnum::FINISHED);
        $room->setOwner($owner);

        $this->manager->persist($room);
        $this->manager->flush();

        return $room;
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            GameModeFixtures::class,
        ];
    }
}
