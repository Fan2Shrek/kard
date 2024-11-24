<?php

namespace App\Service\GameManager\GameMode;

use App\Model\Card\Card;

interface GameModeInterface
{
    public function play(Card $card): void;

    public function getName(): string;
}
