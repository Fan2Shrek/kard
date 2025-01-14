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

    public function get(string|User $player, Room $room): ?Hand
    {
        if ($player instanceof User) {
            $player = (string) $player->getId();
        }

        $deck = $this->redisConnection->get($this->getKey($player, $room));

        if ('' === $deck) {
            return null;
        }

        return $this->serializer->deserialize($deck, Hand::class, 'json');
    }

    public function getRaw(User $player, Room $room): ?string
    {
        $player = (string) $player->getId();

        $deck = $this->redisConnection->get($this->getKey($player, $room));

        if ('' === $deck) {
            return null;
        }

        return $deck;
    }

    public function save(User $player, Room $room, Hand $hand): void
    {
        $player = (string) $player->getId();

        $this->redisConnection->set($this->getKey($player, $room), $this->serializer->serialize($hand, 'json'));
    }

    private function getKey(string $player, Room $room): string
    {
        return sha1(\sprintf('%s:%s', $room->getId(), $player));
    }
}
