<?php

namespace App\Model;

use App\Model\Card\Hand;

final class Player
{
    public function __construct(
        private readonly Hand $hand,
    ) {
    }
}
