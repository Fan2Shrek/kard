<?php

declare(strict_types=1);

namespace App\Service\Game\State;

use App\Entity\Game\GameEvent as GameEventEntity;
use App\Game\State\GameEvent;
use App\Repository\Game\GameEventRepository;

final class DoctrineGameEventRepository implements GameEventRepositoryInterface
{
    public function __construct(
        private GameEventRepository $gameEventRepository,
    ) {}

    public function save(GameEvent $gameEvent): void
    {
        $gameEvent = GameEventEntity::createFromGameEvent($gameEvent);

        $this->gameEventRepository->save($gameEvent);
    }

    public function getEventsSince(?int $lastEventId, string $roomId): array
    {
        $gameEventEntities = $this->gameEventRepository->getEventSince($lastEventId, $roomId);

        return array_map($this->entityToGameEvent(...), $gameEventEntities);
    }

    private function entityToGameEvent(GameEventEntity $gameEvent): GameEvent
    {
        return new GameEvent($gameEvent->getId(), $gameEvent->getType(), GameEvent::PLAYER_EVENT, $gameEvent->getData());
    }
}
