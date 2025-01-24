<?php

declare(strict_types=1);

namespace App\Entity\Purchase;

use App\Repository\OneTimePurchaseRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OneTimePurchaseRepository::class)]
final class OneTimePurchase extends Purchase
{
}
