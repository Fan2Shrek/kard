<?php

use App\Entity\GameEventLog;
use App\Entity\GameMode;
use App\Entity\Room;
use App\Enum\Card\Rank;
use App\Enum\Card\Suit;
use App\Game\Event\CardPlayedEvent;
use App\Game\EventListener\GameEventPersistenceSubscriber;
use App\Game\Mode\GameModeEnum;
use App\Game\Model\Card\Card;
use App\Game\Model\Player;
use App\Repository\GameEventLogRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

covers(GameEventPersistenceSubscriber::class);

test('onGameEvent() persiste un event de jeu dans le repository', function () {
    $room = new Room(new GameMode(GameModeEnum::CRAZY_EIGHTS), Uuid::uuid4());
    $player = new Player('1', 'Player 1');
    $event = new CardPlayedEvent($room, $player, [new Card(Rank::SEVEN, Suit::HEARTS)]);

    $normalizedPayload = ['player' => ['id' => '1'], 'cards' => [['rank' => '7', 'suit' => 'h']]];

    $normalizer = $this->createMock(NormalizerInterface::class);
    $normalizer->expects($this->once())->method('normalize')->with($event)->willReturn($normalizedPayload);

    $repository = $this->createMock(GameEventLogRepository::class);
    $repository->expects($this->once())->method('save')->with(
        $this->callback(function (GameEventLog $log) use ($room, $normalizedPayload): bool {
            expect($log->getRoom())->toBe($room);
            expect($log->getType())->toBe('card_played');
            expect($log->getPayload())->toBe($normalizedPayload);

            return true;
        }),
    );

    $subscriber = new GameEventPersistenceSubscriber($repository, $normalizer);
    $subscriber->onGameEvent($event);
});
