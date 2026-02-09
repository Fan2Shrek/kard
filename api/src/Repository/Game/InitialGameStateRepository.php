<?php

declare(strict_types=1);

namespace App\Repository\Game;

use App\Entity\Game\InitialGameState;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<InitialGameState>
 */
class InitialGameStateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InitialGameState::class);
    }

    public function save(InitialGameState $initialGameState, bool $flush = true): void
    {
        $this->getEntityManager()->persist($initialGameState);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
