<?php

namespace App\DataFixtures;

use App\Entity\Leaderboard;
use App\Repository\ResultRepository;
use App\Repository\UserRepository;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class LeaderboardFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    public function __construct(
        private ResultRepository $resultRepository,
        private UserRepository $userRepository,
    ) {
    }

    protected function getEntityClass(): string
    {
        return Leaderboard::class;
    }

    protected function getData(): iterable
    {
        $users = $this->userRepository->findAll();

        foreach ($users as $user) {
            $winsCount = $this->resultRepository->createQueryBuilder('r')
                ->select('COUNT(r.id)')
                ->where('r.winner = :user')
                ->setParameter('user', $user)
                ->getQuery()
                ->getSingleScalarResult();

            if ($winsCount > 0) {
                yield [
                    'player' => $user,
                    'winsNumber' => $winsCount,
                ];
            }
        }
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            ResultFixtures::class,
        ];
    }
}
