<?php

declare(strict_types=1);

namespace App\Enum;

enum GameEventTypeEnum: string
{
	case SCORE_UPDATED = 'update_score';
	case CARD_DISCARDED = 'card_discarded';
	case ROUND_STARTED = 'round_started';
	case ROUND_ENDED = 'round_ended';
	case TURN_PLAYED = 'turn_played';
	case TURN_ENDED = 'turn_ended';
}
