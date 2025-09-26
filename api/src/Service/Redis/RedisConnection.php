<?php

namespace App\Service\Redis;

final class RedisConnection
{
    public function __construct(
        private \Redis $connection,
    ) {
    }

    public function get(string $key): string
    {
        return $this->connection->get($key);
    }

    public function set(string $key, string $value): void
    {
        $this->connection->set($key, $value);
    }

    public function del(string $key): void
    {
        $this->connection->del($key);
    }
}
