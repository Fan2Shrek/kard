<?php

declare(strict_types=1);

namespace App\Service\Redis;

use Symfony\Component\Serializer\SerializerInterface;

class RedisClient
{
    public function __construct(
        private RedisConnection $connection,
        private SerializerInterface $serializer,
    ) {}

    /**
     * @template T of object
     *
     * @param string $key
     * @param class-string<T> $type
     *
     * @return ?T
     */
    public function get(string $key, string $type): ?object
    {
        $data = $this->connection->get($key);

        return $this->serializer->deserialize($data, $type, 'json');
    }

    public function set(string $key, object $value): void
    {
        $data = $this->serializer->serialize($value, 'json');

        $this->connection->set($key, $data);
    }
}
