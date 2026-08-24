<?php

declare(strict_types=1);

namespace App\Game\EventListener;

use App\Entity\GameEventLog;
use App\Game\Event\AbstractGameEvent;
use App\Game\Event\CardDrawnEvent;
use App\Game\Event\CardOrNothingCalledEvent;
use App\Game\Event\CardPlayedEvent;
use App\Game\Event\PlayOrderReversedEvent;
use App\Game\Event\RoundEndedEvent;
use App\Game\Event\SuitChangedEvent;
use App\Game\Event\TurnSkippedEvent;
use App\Repository\GameEventLogRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class GameEventPersistenceSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly GameEventLogRepository $gameEventLogRepository,
        private readonly NormalizerInterface $normalizer,
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
        /** @var array<string, mixed> $payload */
        $payload = $this->normalizer->normalize($event);

        $this->gameEventLogRepository->save(new GameEventLog(
            $event->room,
            $event->getType(),
            $payload,
        ));
    }
}
