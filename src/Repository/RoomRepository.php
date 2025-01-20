<?php

namespace App\Repository;

use App\Entity\Room;
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

    public function save(Room $room): void
    {
        $this->getEntityManager()->persist($room);
        $this->getEntityManager()->flush();
    }
}
