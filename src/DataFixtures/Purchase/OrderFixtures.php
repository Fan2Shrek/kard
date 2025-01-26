<?php

namespace App\DataFixtures\Purchase;

use App\DataFixtures\AbstractFixtures;
use App\DataFixtures\UserFixtures;
use App\Entity\Purchase\Order;
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
            'user' => $this->getReference('User_1'),
        ];
    }

    protected function postInstantiate(object $entity): void
    {
        if (!$entity instanceof Order) {
            return;
        }

        $entity->addPurchase($this->getReference('OneTimePurchase_1'));
        $entity->addPurchase($this->getReference('OneTimePurchase_2'));
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            OneTimePurchaseFixtures::class,
        ];
    }
}
