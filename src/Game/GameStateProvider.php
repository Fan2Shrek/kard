<?php

namespace App\Game;

use App\Entity\Room;
use App\Game\Model\GameState;
use App\Game\Model\Player;
use App\Service\Redis\RedisConnection;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class GameStateProvider
{
    public function __construct(
        private readonly RedisConnection $redis,
        private readonly SerializerInterface $serializer,
    ) {
    }

    public function provide(Room $room): GameState
    {
        if ('' === $ctx = $this->redis->get($this->getKey($room))) {
            $ctx = $this->createContext($room);
        }

        return is_string($ctx) ? $this->serializer->deserialize($ctx, GameState::class, 'json', [
            AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true,
        ]) : $ctx;
    }

    public function save(GameState $ctx): void
    {
        $this->redis->set($this->getKey($ctx->getRoom()), $this->serializer->serialize($ctx, 'json'));
    }

    public function clear(Room $room): void
    {
        $this->redis->del($this->getKey($room));
    }

    private function createContext(Room $room): GameState
    {
        $players = array_map(fn ($u): Player => Player::fromUser($u), $room->getParticipants()->toArray());

        return new GameState(
            $room->getId(),
            $room,
            $players,
            $players[0],
        );
    }

    private function getKey(Room $room): string
    {
        return sha1('game-context--'.$room->getId());
    }
}
