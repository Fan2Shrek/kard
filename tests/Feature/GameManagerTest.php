<?php

use App\Entity\GameMode;
use App\Entity\Room;
use App\Entity\User;
use App\Enum\Card\Rank;
use App\Enum\Card\Suit;
use App\Game\Card\HandRepositoryInterface;
use App\Game\Event\CardPlayedEvent;
use App\Game\GameManager;
use App\Game\GameStateProvider;
use App\Game\Mode\CrazyEightsGameMode;
use App\Game\Mode\GameModeEnum;
use App\Game\Model\Card\Card;
use App\Game\Model\Card\Hand;
use App\Game\Model\GameState;
use App\Game\Model\Player;
use App\Game\Model\Turn;
use Psr\Container\ContainerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventDispatcher;

covers(GameManager::class);

test("play() joue un tour normal : sauvegarde la main et l'état, dispatch les events, sans passer par Mercure directement", function () {
    $room = new Room(new GameMode(GameModeEnum::CRAZY_EIGHTS), Uuid::uuid4());

    $player1 = new Player('1', 'Player 1', 2);
    $player2 = new Player('2', 'Player 2', 1);

    $state = new GameState(
        'room-id',
        $room,
        [$player1, $player2],
        $player1,
        [new Turn([new Card(Suit::HEARTS, Rank::SEVEN)])],
    );

    $handRepository = new class implements HandRepositoryInterface {
        /** @var array<string, Hand> */
        public array $hands = [];

        /** @var array<string, Hand> */
        public array $saved = [];

        public function get(string|User $player, Room $room): ?Hand
        {
            return $this->hands[$player] ?? null;
        }

        public function getRaw(User $player, Room $room): ?string
        {
            return null;
        }

        public function save(string|User $player, Room $room, Hand $hand): void
        {
            $this->saved[$player] = $hand;
        }
    };
    $handRepository->hands['1'] = new Hand([
        new Card(Suit::SPADES, Rank::SEVEN),
        new Card(Suit::SPADES, Rank::EIGHT),
    ]);

    $gameStateProvider = $this->createMock(GameStateProvider::class);
    $gameStateProvider->method('provide')->willReturn($state);
    $gameStateProvider->expects($this->once())->method('save')->with($state);

    $dispatchedEvents = [];
    $eventDispatcher = new EventDispatcher();
    $eventDispatcher->addListener(CardPlayedEvent::class, function (CardPlayedEvent $event) use (&$dispatchedEvents): void {
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
                default => throw new \LogicException(\sprintf('Unexpected service "%s" requested', $id)),
            };
        }

        public function has(string $id): bool
        {
            return true;
        }
    };

    $gameMode = new CrazyEightsGameMode($handRepository);

    $gameManager = new GameManager([$gameMode], $container, $gameStateProvider, $handRepository);

    $gameManager->play($room, $player1, [new Card(Suit::SPADES, Rank::SEVEN)]);

    expect($handRepository->saved['1']->getCards())->toHaveCount(1);
    expect($player1->cardsCount)->toBe(1);
    expect($state->getCurrentPlayer()->id)->toBe('2');

    expect($dispatchedEvents)->toHaveCount(1);
    expect($dispatchedEvents[0])->toBeInstanceOf(CardPlayedEvent::class);
    expect($dispatchedEvents[0]->player->id)->toBe('1');
});
