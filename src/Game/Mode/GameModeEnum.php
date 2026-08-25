<?php

declare(strict_types=1);

namespace App\Game\Mode;

enum GameModeEnum: string
{
    case PRESIDENT = 'president';
    case CRAZY_EIGHTS = 'crazy_eights';
}
