<?php

namespace App\Repository;

use App\Entity\GameMode;
use App\Entity\GameModeDescription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GameModeDescription>
 */
class GameModeDescriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameModeDescription::class);
    }

    public function findByGameMode(GameMode $gameMode): ?GameModeDescription
    {
        return $this->findOneBy(['gameMode' => $gameMode]);
    }

    /**
     * @param GameMode[] $gameModes
     *
     * @return GameModeDescription[]
     */
    public function findAllByGameMode(array $gameModes): array
    {
        return $this->createQueryBuilder('gm')
            ->where('gm.gameMode in (:gameMode)')
            ->setParameter('gameMode', $gameModes)
            ->getQuery()
            ->getResult()
        ;
    }
}
