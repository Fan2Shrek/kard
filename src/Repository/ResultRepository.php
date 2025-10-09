<?php

namespace App\Repository;

use App\Entity\Result;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Result>
 */
class ResultRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Result::class);
    }

    /**
     * @return array{
     *      user: string,
     *      wins: int
     * }|null
     */
    public function findYesterdayBestPlayer(): ?array
    {
        return $this->createQueryBuilder('r')
            ->select('IDENTITY(r.winner) as user', 'COUNT(r.id) as wins')
            ->leftJoin('r.winner', 'u')
            ->where('r.date >= :yesterday')
            ->setParameter('yesterday', new \DateTime('yesterday'))
            ->groupBy('user')
            ->orderBy('wins', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countGamesPlayedByUser(User $user): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(DISTINCT r.id)')
            ->leftJoin('r.room', 'room')
            ->leftJoin('room.participants', 'p')
            ->where('r.winner = :user OR p = :user OR room.owner = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<array{date: \DateTime, gameMode: \App\Service\GameManager\GameMode\GameModeEnum|null, result: 'Victoire'|'Défaite'}>
     */
    public function findRecentGamesByUser(User $user, int $limit = 5): array
    {
        return $this->createQueryBuilder('r')
            ->select('r.date', 'gm.value as gameMode',
                'CASE WHEN r.winner = :user THEN \'Victoire\' ELSE \'Défaite\' END as result')
            ->leftJoin('r.room', 'room')
            ->leftJoin('room.participants', 'p')
            ->leftJoin('room.gameMode', 'gm')
            ->where('r.winner = :user OR p = :user OR room.owner = :user')
            ->setParameter('user', $user)
            ->orderBy('r.date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function save(Result $result): void
    {
        $this->getEntityManager()->persist($result);
        $this->getEntityManager()->flush();
    }
}
