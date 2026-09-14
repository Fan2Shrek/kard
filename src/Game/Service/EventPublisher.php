<?php

declare(strict_types=1);

namespace App\Game\Service;

use App\Entity\Room;
use App\Game\Model\Event\GameEvent;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Serializer\SerializerInterface;

final class EventPublisher
{
    public function __construct(
        private HubInterface $hub,
        private SerializerInterface $serializer,
    ) {
    }

    /**
     * @param GameEvent[] $events
     */
    public function publish(Room $room, array $events, ?string $playerId = null): void
    {
        $update = new Update(
            \sprintf('game-%s', $room->getId()->toString()),
            $this->serializer->serialize($this->getPayload($events, $playerId), 'json'),
        );

        $this->hub->publish($update);
    }

    /**
     * @param GameEvent[] $events
     *
     * @return array{events: GameEvent[], playerId: string|null}
     */
    private function getPayload(array $events, ?string $playerId): array
    {
        return [
            'events' => $events,
            // whoever caused this batch already got the resulting state in their
            // POST response - it lets them skip refetching their own echo
            'playerId' => $playerId,
        ];
    }
}
