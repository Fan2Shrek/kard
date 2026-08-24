<?php

declare(strict_types=1);

namespace App\Enum;

enum GameEventTypeEnum: string
{
	case SCORE_UPDATED = 'update_score';
}
