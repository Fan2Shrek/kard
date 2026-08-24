<?php

declare(strict_types=1);

namespace App\Game\Model\Event;

use App\Enum\GameEventTypeEnum;

final readonly class GameEvent
{
	public function __construct(
		public GameEventTypeEnum $type,
		public array $payload = [],
	) {
	}
}
