<?php

namespace App\Repository;

use App\Entity\Result;
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
}
