<?php

declare(strict_types=1);

namespace App\Game\StateProvider;

use App\Game\Model\State\GameState;
use App\Service\Redis\RedisConnection;

final class RedisGameStateProvider implements GameStateProviderInterface
{
    public function __construct(
        private RedisConnection $redis,
    ) {
    }

    public function get(string $id): GameState
    {
		if ('' === $state = $this->redis->get($id)) {
			throw new \RuntimeException('Game state not found');
		}

		$state = unserialize($state);

		if (!$state instanceof GameState) {
			throw new \RuntimeException('Invalid game state');
		}

		return $state;
    }

    public function save(string $id, GameState $state): void
    {
        $this->redis->set($id, serialize($state));
    }

    public function clear(string $id): void
    {
        $this->redis->del($id);
    }
}
