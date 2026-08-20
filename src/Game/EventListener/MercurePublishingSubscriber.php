<?php

declare(strict_types=1);

namespace App\Game\EventListener;

use App\Game\Event\AbstractGameEvent;
use App\Game\Event\CardDrawnEvent;
use App\Game\Event\CardOrNothingCalledEvent;
use App\Game\Event\CardPlayedEvent;
use App\Game\Event\PlayOrderReversedEvent;
use App\Game\Event\RoundEndedEvent;
use App\Game\Event\SuitChangedEvent;
use App\Game\Event\TurnSkippedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Serializer\SerializerInterface;

final class MercurePublishingSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly HubInterface $hub,
        private readonly SerializerInterface $serializer,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CardPlayedEvent::class => 'onGameEvent',
            CardDrawnEvent::class => 'onGameEvent',
            SuitChangedEvent::class => 'onGameEvent',
            PlayOrderReversedEvent::class => 'onGameEvent',
            TurnSkippedEvent::class => 'onGameEvent',
            RoundEndedEvent::class => 'onGameEvent',
            CardOrNothingCalledEvent::class => 'onGameEvent',
        ];
    }

    public function onGameEvent(AbstractGameEvent $event): void
    {
        $this->hub->publish(new Update(
            \sprintf('room-%s', $event->room->getId()),
            $this->serializer->serialize([
                'type' => $event->getType(),
                'data' => $event,
            ], 'json'),
        ));
    }
}
