<?php

namespace App\Service;

use App\Entity\Purchase\Order;

final class OrderManager
{
    public function __construct(
        private StripeClient $stripeClient,
    ) {
    }

    public function doPayment(Order $order): void
    {
        $this->stripeClient->pay($order->getTotal() * 100);

        $order->getUser()->addRole('ROLE_PIGEON');
    }
}
