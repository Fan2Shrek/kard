<?php

declare(strict_types=1);

namespace App\Game\Model\Event;

use App\Enum\GameEventTypeEnum;

final readonly class GameEvent
{
	/**
	 * @param array<string, mixed> $payload
	 */
	public function __construct(
		public GameEventTypeEnum $type,
		public array $payload = [],
	) {
	}
}
