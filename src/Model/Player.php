<?php

namespace App\Model;

use App\Model\Card\Hand;

final class Player
{
    public function __construct(
        public readonly string $name, 
        private ?Hand $hand = null,
    ) {
    }
}
