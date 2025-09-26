<?php

namespace App\DataFixtures\Purchase;

use App\DataFixtures\AbstractFixtures;
use App\DataFixtures\UserFixtures;
use App\Entity\Purchase\OneTimePurchase;
use App\Entity\Purchase\Order;
use App\Entity\User;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

final class OrderFixtures extends AbstractFixtures implements DependentFixtureInterface
{
    protected function getEntityClass(): string
    {
        return Order::class;
    }

    protected function getData(): iterable
    {
        yield [
            'user' => $this->getReference('User_1', User::class),
        ];
    }

    protected function postInstantiate(object $entity): void
    {
        if (!$entity instanceof Order) {
            return;
        }

        $entity->addPurchase($this->getReference('OneTimePurchase_1', OneTimePurchase::class));
        $entity->addPurchase($this->getReference('OneTimePurchase_2', OneTimePurchase::class));
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            OneTimePurchaseFixtures::class,
        ];
    }
}
