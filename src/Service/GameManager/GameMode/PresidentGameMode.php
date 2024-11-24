<?php

namespace App\Service\GameManager\GameMode;

use App\Model\Card\Card;

final class PresidentGameMode implements GameModeInterface
{
    public function getName(): string
    {
        return 'President';
    }

    public function play(Card $card): void
    {
        
    }
}
