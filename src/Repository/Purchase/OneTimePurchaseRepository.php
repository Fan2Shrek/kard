<?php

namespace App\Repository;

use App\Entity\Purchase\OneTimePurchase;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OneTimePurchase>
 */
class OneTimePurchaseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OneTimePurchase::class);
    }
}
