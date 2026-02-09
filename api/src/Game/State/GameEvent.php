<?php

declare(strict_types=1);

namespace App\Game\State;

final readonly class GameEvent
{
    public const PLAYER_EVENT = 'player_event';
    public const GAME_EVENT = 'game_event';

	public const CARD_PLAYED = 'card_played';
	public const CARD_DRAW = 'card_draw';

    public function __construct(
        public int $id,
        public string $type,
        public string $eventOrigin,
        public array $data,
    ) {}

    public static function game(string $type, array $data): self
    {
        return new self(0, $type, self::GAME_EVENT, $data);
    }

    public static function player(string $type, array $data): self
    {
        return new self(0, $type, self::PLAYER_EVENT, $data);
    }
}
