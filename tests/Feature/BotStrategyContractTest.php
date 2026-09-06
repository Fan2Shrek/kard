<?php

use App\Entity\GameMode;
use App\Entity\Room;
use App\Entity\User;
use App\Game\GameManager;
use App\Game\Mode\CrazyEightsGameMode;
use App\Game\Mode\GameModeEnum;
use App\Game\Mode\MenteurGameMode;
use App\Game\Mode\PresidentGameMode;
use App\Game\Model\State\GameState;
use App\Game\Service\EventPublisher;
use App\Game\Service\GameEventApplier;
use App\Game\StateProvider\GameStateProviderInterface;
use App\Service\Bot\BotClient;
use App\Service\Bot\GameAI;
use Psr\Container\ContainerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Serializer\SerializerInterface;

/*
 * Integration test against the real Python bot service.
 *
 * It is the only thing that catches drift between GameStateDTO and what
 * the strategies read: a unit test with a stubbed BotClient can't, because the
 * stub never parses the payload. Skipped when the service isn't reachable.
 */
$modes = [
    'president' => [GameModeEnum::PRESIDENT, fn () => new PresidentGameMode()],
    'crazy_eights' => [GameModeEnum::CRAZY_EIGHTS, fn () => new CrazyEightsGameMode()],
    'menteur' => [GameModeEnum::MENTEUR, fn () => new MenteurGameMode()],
];

foreach ($modes as $label => [$enum, $factory]) {
    test("LIVE $label : 4 bots jouent contre le vrai service Python", function () use ($enum, $factory) {
        $room = new Room(new GameMode($enum), Uuid::uuid4());
        $user = new User('human', 'h@test.com');
        (new ReflectionProperty($user, 'id'))->setValue($user, Uuid::uuid4());
        $room->addParticipant($user);

        for ($i = 0; $i < 4; ++$i) {
            $bot = GameAI::create();
            $room->addBot($bot->id, $bot);
        }

        $saved = null;
        $provider = $this->createMock(GameStateProviderInterface::class);
        $provider->method('get')->willReturnCallback(function () use (&$saved): GameState {
            return $saved;
        });
        $provider->method('save')->willReturnCallback(function ($id, GameState $s) use (&$saved): void {
            $saved = $s;
        });

        $errors = [];
        $logger = new class($errors) extends Psr\Log\NullLogger {
            public function __construct(private array &$errors)
            {
            }

            public function error($message, array $context = []): void
            {
                $e = $context['exception'] ?? null;
                $this->errors[] = $message.': '.($e instanceof Throwable ? $e->getMessage() : '');
            }
        };

        $gameAI = null;
        $container = new class($gameAI, $logger) implements ContainerInterface {
            public function __construct(private ?GameAI &$gameAI, private $logger)
            {
            }

            public function get(string $id): mixed
            {
                return match ($id) {
                    'event_dispatcher' => new EventDispatcher(),
                    'logger' => $this->logger,
                    'game_ai' => $this->gameAI,
                    'user_repository', 'result_repository' => throw new LogicException('n/a'),
                    default => throw new LogicException($id),
                };
            }

            public function has(string $id): bool
            {
                return true;
            }
        };

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->method('serialize')->willReturn('{}');

        $gm = new GameManager(
            [$factory()],
            $container,
            $provider,
            new GameEventApplier(),
            new EventPublisher($this->createMock(HubInterface::class), $serializer),
        );
        // by-ref promoted properties aren't a thing - count on the object itself
        $botClient = new class('http://bot:5000') extends BotClient {
            public int $turns = 0;

            public function play(GameModeEnum $gameMode, object|array $body = []): array
            {
                ++$this->turns;

                return parent::play($gameMode, $body);
            }
        };
        $gameAI = new GameAI($botClient, $gm);

        $room->setConfiguration($gm->getDefaultConfiguration($enum));
        $state = $gm->start($room);

        // the human keeps handing the turn back so only the bots really play.
        // Passing isn't always legal (President won't let you open a round with
        // nothing), so fall back to dropping any card from the hand.
        for ($i = 0; $i < 12; ++$i) {
            if ($state->currentPlayerId !== $user->getId()->toString()) {
                break;
            }

            $hand = array_values($state->getPlayerStateById($user->getId()->toString())->hand->cards);

            try {
                $gm->play($room, $user, []);
            } catch (Throwable) {
                if ([] === $hand) {
                    break;
                }

                try {
                    $gm->play($room, $user, [$hand[0]], ['rank' => '3', 'suit' => 'h']);
                } catch (Throwable) {
                    break;
                }
            }

            $state = $saved;
        }

        // every bot move was accepted by the rules, in every mode
        expect($errors)->toBe([]);
        // and the bots actually did something rather than silently stalling
        expect($botClient->turns)->toBeGreaterThan(0);
    })->skip(
        false === @file_get_contents('http://bot:5000/move/president', false, stream_context_create([
            'http' => ['method' => 'POST', 'header' => 'Content-Type: application/json', 'content' => '{}', 'timeout' => 2, 'ignore_errors' => true],
        ])),
        'bot service unreachable',
    );
}
