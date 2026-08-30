<?php

namespace App\Repository;

use App\Entity\Leaderboard;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Leaderboard>
 */
class LeaderboardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Leaderboard::class);
    }

    public function findByUser(User $user): ?Leaderboard
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.player = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
