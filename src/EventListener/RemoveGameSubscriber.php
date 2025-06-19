<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Enum\GameStatusEnum;
use App\Event\Room\RoomEvent;
use App\Repository\RoomRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class RemoveGameSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private RoomRepository $roomRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'room.created' => 'onRoomCreated',
        ];
    }

    public function onRoomCreated(RoomEvent $event): void
    {
        foreach ($this->roomRepository->findBy(['owner' => $event->room->getOwner(), 'status' => GameStatusEnum::WAITING]) as $room) {
            if ($room->getId() === $event->room->getId()) {
                continue;
            }

            $this->roomRepository->remove($room);
        }
    }
}
