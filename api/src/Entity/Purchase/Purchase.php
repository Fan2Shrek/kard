<?php

namespace App\Entity\Purchase;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discr', type: 'string')]
#[ORM\DiscriminatorMap(['oneTime' => OneTimePurchase::class, 'duration' => DurationPurchase::class])]
abstract class Purchase
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column]
    private int $price;

    #[ORM\Column(length: 255)]
    private string $name;

    public function __construct(string $name, int $price)
    {
        $this->name = $name;
        $this->price = $price;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getNameWithType(): string
    {
        $isOneTimePurchase = $this instanceof OneTimePurchase;
        $additional = $isOneTimePurchase
            ? $this->price.'€'
            : 'abonnement : '.$this->price.'€/mois';

        return sprintf('%s (%s)',
            $this->name,
            $additional
        );
    }
}
