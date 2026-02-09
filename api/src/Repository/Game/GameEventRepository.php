<?php

declare(strict_types=1);

namespace App\Repository\Game;

use App\Entity\Game\GameEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GameEvent>
 */
class GameEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameEvent::class);
    }

    public function save(GameEvent $gameEvent, bool $flush = true): void
    {
        $this->getEntityManager()->persist($gameEvent);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getEventSince(?int $lastEventId, string $roomId): array
    {
        return $this
            ->createQueryBuilder('e')
            ->andWhere('e.id > :lastEventId')
            ->andWhere('e.roomId = :roomId')
            ->setParameter('lastEventId', $lastEventId ?? 0)
            ->setParameter('roomId', $roomId)
            ->orderBy('e.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
