<?php

use App\Entity\GameMode;
use App\Entity\Room;
use App\Entity\User;
use App\Enum\Card\Rank;
use App\Enum\Card\Suit;
use App\Enum\GameEventTypeEnum;
use App\Game\GameManager;
use App\Game\Mode\GameModeEnum;
use App\Game\Mode\PresidentGameMode;
use App\Game\Model\Card\Card;
use App\Game\Model\Card\DiscardPile;
use App\Game\Model\Card\DrawPile;
use App\Game\Model\Card\Hand;
use App\Game\Model\Event\GameEvent;
use App\Game\Model\State\GameState;
use App\Game\Model\State\PlayerState;
use App\Game\Model\State\Round;
use App\Game\Service\EventPublisher;
use App\Game\Service\GameEventApplier;
use App\Game\StateProvider\GameStateProviderInterface;
use Psr\Container\ContainerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Serializer\SerializerInterface;

covers(GameManager::class);

test("play() joue un tour normal : sauvegarde la main et l'état, dispatch les events, sans passer par Mercure directement", function () {
    $room = new Room(new GameMode(GameModeEnum::PRESIDENT), Uuid::uuid4());

    $userId = Uuid::uuid4();
    $user = new User('player1', 'player1@test.com');
    (new ReflectionProperty($user, 'id'))->setValue($user, $userId);

    $card7 = new Card('7s', Rank::SEVEN, Suit::SPADES);
    $card8 = new Card('8s', Rank::EIGHT, Suit::SPADES);
    $card9 = new Card('9s', Rank::NINE, Suit::SPADES);

    // scores pre-set to the *post-play* hand size, so refreshScore() doesn't add
    // an incidental SCORE_UPDATED noise event on top of the ones we assert on.
    // player2 must keep a non-empty hand: a score of 0 means "this player has
    // won" per PresidentGameMode::isGameFinished(), which we don't want here.
    $player1 = new PlayerState($userId->toString(), 'Player 1', 1, new Hand(['7s', '8s']));
    $player2 = new PlayerState('2', 'Player 2', 1, new Hand(['9s']));

    $state = new GameState(
        [$player1, $player2],
        [$userId->toString(), '2'],
        $userId->toString(),
        [0 => new Round(0, [])],
        new DiscardPile([]),
        new DrawPile([]),
        ['7s' => $card7, '8s' => $card8, '9s' => $card9],
    );

    $savedState = null;

    $gameStateProvider = $this->createMock(GameStateProviderInterface::class);
    $gameStateProvider->method('get')->willReturn($state);
    $gameStateProvider->expects($this->once())->method('save')->with(
        $this->anything(),
        $this->callback(function (GameState $newState) use (&$savedState): bool {
            $savedState = $newState;

            return true;
        })
    );

    $dispatchedEvents = [];
    $eventDispatcher = new EventDispatcher();
    $eventDispatcher->addListener(GameEvent::class, function (GameEvent $event) use (&$dispatchedEvents): void {
        $dispatchedEvents[] = $event;
    });

    $container = new class($eventDispatcher) implements ContainerInterface {
        public function __construct(private EventDispatcher $eventDispatcher)
        {
        }

        public function get(string $id): mixed
        {
            return match ($id) {
                'event_dispatcher' => $this->eventDispatcher,
                default => throw new LogicException(\sprintf('Unexpected service "%s" requested', $id)),
            };
        }

        public function has(string $id): bool
        {
            return true;
        }
    };

    $hub = $this->createMock(HubInterface::class);
    $serializer = $this->createMock(SerializerInterface::class);
    $serializer->method('serialize')->willReturn('{}');
    $publisher = new EventPublisher($hub, $serializer);

    $gameMode = new PresidentGameMode();
    $gameManager = new GameManager([$gameMode], $container, $gameStateProvider, new GameEventApplier(), $publisher);

    $gameManager->play($room, $user, ['7s']);

    expect($savedState->getPlayerStateById($userId->toString())->hand->cards)->toBe(['8s']);
    expect($savedState->currentPlayerId)->toBe('2');

    $byType = fn (GameEventTypeEnum $type) => array_values(array_filter(
        $dispatchedEvents,
        fn (GameEvent $e): bool => $type === $e->type
    ));

    expect($byType(GameEventTypeEnum::TURN_PLAYED))->toHaveCount(1);
    expect($byType(GameEventTypeEnum::CARD_DISCARDED))->toHaveCount(1);
    expect($byType(GameEventTypeEnum::TURN_ENDED))->toHaveCount(1);
});
