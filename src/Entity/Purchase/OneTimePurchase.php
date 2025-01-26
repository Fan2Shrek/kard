<?php

declare(strict_types=1);

namespace App\Entity\Purchase;

use App\Repository\Purchase\OneTimePurchaseRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OneTimePurchaseRepository::class)]
class OneTimePurchase extends Purchase
{
}
