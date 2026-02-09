<?php

declare(strict_types=1);

namespace App\Service\Game\State;

use App\Entity\Room;
use App\Game\State\GameState;
use App\Service\Game\GameManager;
use App\Service\Redis\RedisClient;
use Symfony\Component\DependencyInjection\Attribute\WhenNot;

#[WhenNot('test')]
final class RedisGameStateRepository implements GameStateRepositoryInterface
{
    public function __construct(
        private RedisClient $redisClient,
        private GameStateRepositoryInterface $decoratedRepository,
        private GameEventRepositoryInterface $gameEventRepository,
        private GameManager $gameManager,
    ) {}

    public function save(GameState $gameState, Room $room): void
    {
        $this->decoratedRepository->save($gameState, $room);
        $this->redisClient->set($this->getRedisKey($room), $gameState);
    }

    public function get(Room $room): ?GameState
    {
        $gameState = $this->redisClient->get($this->getRedisKey($room), GameState::class);
        if (!$gameState instanceof GameState) {
            $gameState = $this->decoratedRepository->get($room);

            if (!$gameState) {
                return null;
            }
        }
        $previousEventId = $gameState->lastEventid;
        $this->buildGameStateFromEvents($gameState, $room);

        if ($previousEventId !== $gameState->lastEventid) {
            $this->decoratedRepository->save($gameState, $room);
        }

        return $gameState;
    }

    private function buildGameStateFromEvents(GameState $gameState, Room $room): void
    {
        $events = $this->gameEventRepository->getEventsSince($gameState->lastEventid, $room->getId()->toString());

        foreach ($events as $event) {
            $this->gameManager->play($event, $gameState);
        }
    }

    private function getRedisKey(Room $room): string
    {
        return 'game_state'.(string) $room->getId();
    }
}
