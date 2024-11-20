<?php

declare(strict_types=1);

namespace App\Service\Card;

use App\Entity\Room;
use App\Entity\User;
use App\Model\Card\Hand;
use App\Service\Redis\RedisConnection;
use Symfony\Component\Serializer\SerializerInterface;

final class HandRepository
{
    public function __construct(
        private readonly RedisConnection $redisConnection,
        private readonly SerializerInterface $serializer,
    ) {
    }

    public function get(User $player, Room $room): ?Hand
    {
        $deck = $this->redisConnection->get($this->getKey($player, $room));

        if ('' === $deck) {
            return null;
        }

        return $this->serializer->deserialize($deck, Hand::class, 'json');
    }

    public function getRaw(User $player, Room $room): ?string
    {
        $deck = $this->redisConnection->get($this->getKey($player, $room));

        if ('' === $deck) {
            return null;
        }

        return $deck;
    }

    public function save(User $player, Room $room, Hand $hand): void
    {
        $this->redisConnection->set($this->getKey($player, $room), $this->serializer->serialize($hand, 'json'));
    }

    private function getKey(User $player, Room $room): string
    {
        return sha1(\sprintf('%s:%s', $room->getId(), $player->getId()));
    }
}
