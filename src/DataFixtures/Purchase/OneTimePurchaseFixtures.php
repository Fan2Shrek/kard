<?php

declare(strict_types=1);

namespace App\DataFixtures\Purchase;

use App\DataFixtures\AbstractFixtures;
use App\Entity\Purchase\OneTimePurchase;

final class OneTimePurchaseFixtures extends AbstractFixtures
{
    protected function getEntityClass(): string
    {
        return OneTimePurchase::class;
    }

    protected function getData(): iterable
    {
        yield [
            'name' => 'Soutien des développeurs',
            'price' => 10,
        ];

        yield [
            'name' => 'Soutien des développeurs ++',
            'price' => 20,
        ];

        yield [
            'name' => 'Un autre paiement bien cher mais zebi c\'est la vie',
            'price' => 999,
        ];
    }
}
