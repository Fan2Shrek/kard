<?php

declare(strict_types=1);

namespace App\DataFixtures\Purchase;

use App\DataFixtures\AbstractFixtures;
use App\Entity\Purchase\DurationPurchase;

final class DurationPurchaseFixtures extends AbstractFixtures
{
    protected function getEntityClass(): string
    {
        return DurationPurchase::class;
    }

    protected function getData(): iterable
    {
        yield [
            'name' => 'Abonnement 1 mois',
            'price' => 10,
            'duration' => 30,
        ];

        yield [
            'name' => 'Abonnement 3 mois',
            'price' => 25,
            'duration' => 90,
        ];

        yield [
            'name' => 'Abonnement 6 mois',
            'price' => 45,
            'duration' => 180,
        ];

        yield [
            'name' => 'Abonnement 1 an',
            'price' => 80,
            'duration' => 365,
        ];
    }
}
