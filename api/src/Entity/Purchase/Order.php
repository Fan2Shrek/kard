<?php

namespace App\Entity\Purchase;

use App\Entity\User;
use App\Repository\Purchase\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: 'order_table')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    /**
     * @var Collection<int, Purchase>
     */
    #[ORM\ManyToMany(targetEntity: Purchase::class)]
    private Collection $purchases;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct(User $user)
    {
        $this->purchases = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->user = $user;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return Collection<int, Purchase>
     */
    public function getPurchases(): Collection
    {
        return $this->purchases;
    }

    public function addPurchase(Purchase $purchase): static
    {
        if (!$this->purchases->contains($purchase)) {
            $this->purchases->add($purchase);
        }

        return $this;
    }

    public function removePurchase(Purchase $purchase): static
    {
        $this->purchases->removeElement($purchase);

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getTotal(): float
    {
        $total = 0;

        foreach ($this->purchases as $purchase) {
            $total += $purchase->getPrice();
        }

        return $total;
    }
}
