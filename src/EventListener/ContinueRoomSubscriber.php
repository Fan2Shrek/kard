<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Room;
use App\Game\Event\GameFinishedEvent;
use App\Repository\RoomRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class ContinueRoomSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private HubInterface $hub,
        private SerializerInterface $serializer,
        private RouterInterface $router,
        private RoomRepository $roomRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            GameFinishedEvent::class => 'onRoomFinished',
        ];
    }

    public function onRoomFinished(GameFinishedEvent $event): void
    {
        $newRoom = new Room($event->room->getGameMode());
        $newRoom->setOwner($event->room->getOwner());

        foreach ($event->room->getParticipants() as $player) {
            $newRoom->addParticipant($player);
        }
        $newRoom->setConfiguration($event->room->getConfiguration());

        $this->roomRepository->save($newRoom);

        $this->hub->publish(new Update(
            sprintf('game-%s', $event->room->getId()),
            $this->serializer->serialize([
                'action' => 'end',
                'data' => [
                    'context' => $event->context,
                    'winner' => ['username' => $event->winner->getUsername()],
                    'url' => $this->router->generate('waiting', ['id' => $newRoom->getId()]),
                ],
            ], 'json'),
        ));
    }
}
