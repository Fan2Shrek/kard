<?php

namespace App\Repository;

use App\Entity\Room;
use App\Entity\User;
use App\Enum\GameStatusEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Room>
 */
class RoomRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Room::class);
    }

    /**
     * @return Room[]
     */
    public function findAllCurrent(): array
    {
        return $this->findBy(
            ['status' => GameStatusEnum::WAITING],
            ['id' => 'DESC'],
        );
    }

    /**
     * @return Room[]
     */
    public function findAllRoomWithPlayer(User $player): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.status = :status')
            ->andWhere(':player MEMBER OF r.participants')
            ->setParameter('status', GameStatusEnum::FINISHED)
            ->setParameter('player', $player)
            ->getQuery()
            ->getResult();
    }

    public function save(Room $room): void
    {
        $this->getEntityManager()->persist($room);
        $this->getEntityManager()->flush();
    }

    public function remove(Room $room): void
    {
        $this->getEntityManager()->remove($room);
        $this->getEntityManager()->flush();
    }
}
