<?php

declare(strict_types=1);

namespace App\Service\GameManager\GameMode;

enum GameModeEnum: string
{
    case PRESIDENT = 'president';
    case AMERICAN_EIGHT = 'american_eight';
}
