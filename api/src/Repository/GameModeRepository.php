<?php

namespace App\Repository;

use App\Entity\GameMode;
use App\Service\GameManager\GameMode\GameModeEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GameMode>
 */
class GameModeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameMode::class);
    }

    /**
     * @return GameMode[]
     */
    public function findActiveGameModes(): array
    {
        return $this->createQueryBuilder('gm')
            ->where('gm.active = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();
    }

    public function findByGameMode(GameModeEnum $gameMode): ?GameMode
    {
        return $this->findOneBy(['value' => $gameMode]);
    }
}
