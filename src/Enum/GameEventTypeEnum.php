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
	case CARD_DRAWN = 'card_drawn';
	case CARD_GIVEN = 'card_given';
	case TURN_SKIPPED = 'turn_skipped';
	case REVERSE_PLAYERS_ORDER = 'reverse_players_order';

	// PRESIDENT
	case CARD_OR_NOTHING_CALLED = 'card_or_nothing_called';

	// CRAZY EIGHTS
	case SUIT_CHANGED = 'suit_changed';

	// MENTEUR
	case ROUND_RESET = 'round_reset';
	case CURRENT_PLAYER_SET = 'current_player_set';
	case CHALLENGE_RESULT = 'challenge_result';
}
