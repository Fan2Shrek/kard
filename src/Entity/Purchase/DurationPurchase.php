<?php

declare(strict_types=1);

namespace App\Entity\Purchase;

use App\Repository\Purchase\DurationPurchaseRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DurationPurchaseRepository::class)]
final class DurationPurchase extends Purchase
{
    #[ORM\Column]
    private int $duration;

    public function __construct(string $name, int $price, int $duration)
    {
        parent::__construct($name, $price);

        $this->duration = $duration;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): static
    {
        $this->duration = $duration;

        return $this;
    }
}
