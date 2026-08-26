<?php

use App\Entity\GameMode;
use App\Entity\Room;
use App\Entity\User;
use App\Enum\Card\Rank;
use App\Enum\Card\Suit;
use App\Enum\GameStatusEnum;
use App\Game\Event\GameFinishedEvent;
use App\Game\GameManager;
use App\Game\Mode\GameModeEnum;
use App\Game\Mode\PresidentGameMode;
use App\Game\Model\Card\Card;
use App\Game\Model\Card\DiscardPile;
use App\Game\Model\Card\DrawPile;
use App\Game\Model\Card\Hand;
use App\Game\Model\GameConfiguration;
use App\Game\Model\State\GameState;
use App\Game\Model\State\PlayerState;
use App\Game\Model\State\Round;
use App\Game\Service\EventPublisher;
use App\Game\Service\GameEventApplier;
use App\Game\StateProvider\GameStateProviderInterface;
use App\Service\Bot\BotClient;
use App\Service\Bot\GameAI;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use Ramsey\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Serializer\SerializerInterface;

covers(GameManager::class, GameAI::class);

test("playAs() enchaîne le tour du bot dès que c'est à lui de jouer", function () {
    $room = new Room(new GameMode(GameModeEnum::PRESIDENT), Uuid::uuid4());
    $room->setConfiguration(GameConfiguration::fromArray([]));

    $userId = Uuid::uuid4();
    $user = new User('player1', 'player1@test.com');
    (new ReflectionProperty($user, 'id'))->setValue($user, $userId);

    $cards = [
        '7s' => new Card('7s', Rank::SEVEN, Suit::SPADES),
        '8s' => new Card('8s', Rank::EIGHT, Suit::SPADES),
        '9s' => new Card('9s', Rank::NINE, Suit::SPADES),
        '10s' => new Card('10s', Rank::TEN, Suit::SPADES),
    ];

    $human = new PlayerState($userId->toString(), 'Player 1', 1, new Hand(['7s', '8s']));
    $bot = new PlayerState('bot-1', 'Brave Dragon', 1, new Hand(['9s', '10s']), true);

    $state = new GameState(
        [$human, $bot],
        [$userId->toString(), 'bot-1'],
        $userId->toString(),
        [0 => new Round(0, [])],
        new DiscardPile([]),
        new DrawPile([]),
        $cards,
    );

    // the provider is the only shared memory between the two nested playAs() calls
    $saved = $state;
    $gameStateProvider = $this->createMock(GameStateProviderInterface::class);
    // NB: a closure, not an arrow fn - arrow fns capture by value
    $gameStateProvider->method('get')->willReturnCallback(function () use (&$saved): GameState {
        return $saved;
    });
    $gameStateProvider->method('save')->willReturnCallback(function (string $id, GameState $new) use (&$saved): void {
        $saved = $new;
    });

    $seenPayloads = [];
    $botClient = $this->createMock(BotClient::class);
    $botClient->method('play')->willReturnCallback(
        function (GameModeEnum $mode, array $payload) use (&$seenPayloads): array {
            $seenPayloads[] = $payload;

            return ['cards' => ['9s'], 'data' => []];
        }
    );

    $gameAI = null;
    $container = new class($gameAI) implements ContainerInterface {
        public function __construct(private ?GameAI &$gameAI)
        {
        }

        public function get(string $id): mixed
        {
            return match ($id) {
                'event_dispatcher' => new EventDispatcher(),
                'game_ai' => $this->gameAI,
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

    $gameManager = new GameManager(
        [new PresidentGameMode()],
        $container,
        $gameStateProvider,
        new GameEventApplier(),
        new EventPublisher($hub, $serializer),
    );
    $gameAI = new GameAI($botClient, $gameManager);

    $gameManager->play($room, $user, ['7s']);

    // the bot played on its own, right after the human
    expect($seenPayloads)->toHaveCount(1);
    expect(array_values($saved->getPlayerStateById('bot-1')->hand->cards))->toBe(['10s']);
    expect($saved->currentPlayerId)->toBe($userId->toString());

    // it only ever saw its own hand, plus the cards already on the table
    expect($seenPayloads[0]['hand'])->toBe(['9s', '10s']);
    expect(array_keys($seenPayloads[0]['cards']))->toBe(['9s', '10s', '7s']);
    expect($seenPayloads[0]['round']['turns'])->toHaveCount(1);
    expect($seenPayloads[0]['round']['turns'][0]['cardIds'])->toBe(['7s']);
    expect($seenPayloads[0]['players'])->toBe([
        ['id' => $userId->toString(), 'cardsCount' => 1, 'isBot' => false],
        ['id' => 'bot-1', 'cardsCount' => 2, 'isBot' => true],
    ]);
});

test('start() reveille le bot quand la partie ouvre sur son tour', function () {
    $room = new Room(new GameMode(GameModeEnum::PRESIDENT), Uuid::uuid4());

    $user = new User('player1', 'player1@test.com');
    (new ReflectionProperty($user, 'id'))->setValue($user, Uuid::uuid4());
    $room->addParticipant($user);

    $bot = GameAI::create();
    $room->addBot($bot->id, $bot);

    $saved = null;
    $gameStateProvider = $this->createMock(GameStateProviderInterface::class);
    $gameStateProvider->method('get')->willReturnCallback(function () use (&$saved): GameState {
        return $saved;
    });
    $gameStateProvider->method('save')->willReturnCallback(function (string $id, GameState $new) use (&$saved): void {
        $saved = $new;
    });

    $called = 0;
    $botClient = $this->createMock(BotClient::class);
    $botClient->method('play')->willReturnCallback(
        function (GameModeEnum $mode, array $payload) use (&$called): array {
            ++$called;

            // play the first card in hand - always legal to open a President round
            return ['cards' => [$payload['hand'][0]], 'data' => []];
        }
    );

    $gameAI = null;
    $container = new class($gameAI) implements ContainerInterface {
        public function __construct(private ?GameAI &$gameAI)
        {
        }

        public function get(string $id): mixed
        {
            return match ($id) {
                'event_dispatcher' => new EventDispatcher(),
                'logger' => new NullLogger(),
                'game_ai' => $this->gameAI,
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

    $gameManager = new GameManager(
        [new PresidentGameMode()],
        $container,
        $gameStateProvider,
        new GameEventApplier(),
        new EventPublisher($hub, $serializer),
    );
    $gameAI = new GameAI($botClient, $gameManager);

    $room->setConfiguration($gameManager->getDefaultConfiguration(GameModeEnum::PRESIDENT));

    $state = $gameManager->start($room);

    // the bot is dealt in like anyone else
    expect(array_keys($state->players))->toContain($bot->id);
    expect($state->getPlayerStateById($bot->id)->hand->count())->toBeGreaterThan(0);

    // whoever the shuffle put first, start() never comes back waiting on a bot
    expect($state->currentPlayerId)->toBe($user->getId()->toString());
    expect($called)->toBe($bot->id === $state->playerOrder[0] ? $called : 0);
});

test('un bot qui gagne declenche quand meme la fin de partie', function () {
    $room = new Room(new GameMode(GameModeEnum::PRESIDENT), Uuid::uuid4());
    $room->setConfiguration(GameConfiguration::fromArray([]));

    $userId = Uuid::uuid4();
    $user = new User('player1', 'player1@test.com');
    (new ReflectionProperty($user, 'id'))->setValue($user, $userId);

    $cards = [
        '7s' => new Card('7s', Rank::SEVEN, Suit::SPADES),
        '8s' => new Card('8s', Rank::EIGHT, Suit::SPADES),
        '9s' => new Card('9s', Rank::NINE, Suit::SPADES),
    ];

    // the human keeps a card in hand so the bot is the one that runs out first
    $human = new PlayerState($userId->toString(), 'Player 1', 1, new Hand(['7s', '8s']));
    $bot = new PlayerState('bot-1', 'Brave Dragon', 1, new Hand(['9s']), true);

    $state = new GameState(
        [$human, $bot],
        [$userId->toString(), 'bot-1'],
        $userId->toString(),
        [0 => new Round(0, [])],
        new DiscardPile([]),
        new DrawPile([]),
        $cards,
    );

    $saved = $state;
    $gameStateProvider = $this->createMock(GameStateProviderInterface::class);
    $gameStateProvider->method('get')->willReturnCallback(function () use (&$saved): GameState {
        return $saved;
    });
    $gameStateProvider->method('save')->willReturnCallback(function (string $id, GameState $new) use (&$saved): void {
        $saved = $new;
    });

    $botClient = $this->createMock(BotClient::class);
    $botClient->method('play')->willReturn(['cards' => ['9s'], 'data' => []]);

    $finished = [];
    $eventDispatcher = new EventDispatcher();
    $eventDispatcher->addListener(GameFinishedEvent::class, function (GameFinishedEvent $e) use (&$finished): void {
        $finished[] = $e;
    });

    $gameAI = null;
    $container = new class($gameAI, $eventDispatcher) implements ContainerInterface {
        public function __construct(private ?GameAI &$gameAI, private EventDispatcher $eventDispatcher)
        {
        }

        public function get(string $id): mixed
        {
            return match ($id) {
                'event_dispatcher' => $this->eventDispatcher,
                'logger' => new NullLogger(),
                'game_ai' => $this->gameAI,
                // a bot winner must never reach the leaderboard - it has no User row
                'user_repository', 'result_repository' => throw new LogicException("repository \"$id\" must not be used for a bot winner"),
                default => throw new LogicException($id),
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

    $gameManager = new GameManager(
        [new PresidentGameMode()],
        $container,
        $gameStateProvider,
        new GameEventApplier(),
        new EventPublisher($hub, $serializer),
    );
    $gameAI = new GameAI($botClient, $gameManager);

    $gameManager->play($room, $user, ['7s']);

    // the front only learns the game is over through this event
    expect($finished)->toHaveCount(1);
    expect($finished[0]->winner->playerName)->toBe('Brave Dragon');
    expect($finished[0]->winner->isBot)->toBeTrue();
    expect($room->getStatus())->toBe(GameStatusEnum::FINISHED);
});
