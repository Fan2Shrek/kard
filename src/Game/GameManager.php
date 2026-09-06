<?php

namespace App\Game;

use App\Entity\Result;
use App\Entity\Room;
use App\Entity\User;
use App\Enum\GameStatusEnum;
use App\Game\Builder\DeckBuilder;
use App\Game\Builder\GameConfigurationBuilder;
use App\Game\Event\GameFinishedEvent;
use App\Game\Exception\RuleException;
use App\Game\Mode\GameModeEnum;
use App\Game\Mode\GameModeInterface;
use App\Game\Mode\SetupGameModeInterface;
use App\Game\Model\Card\Card;
use App\Game\Model\Card\Deck;
use App\Game\Model\Card\DiscardPile;
use App\Game\Model\Card\DrawPile;
use App\Game\Model\Card\Hand;
use App\Game\Model\Event\GameEvent;
use App\Game\Model\GameConfiguration;
use App\Game\Model\GameContext;
use App\Game\Model\State\GameState;
use App\Game\Model\State\PlayerState;
use App\Game\Service\EventPublisher;
use App\Game\Service\GameEventApplier;
use App\Game\StateProvider\GameStateProviderInterface;
use App\Repository\ResultRepository;
use App\Repository\UserRepository;
use App\Service\Bot\GameAI;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

final class GameManager implements ServiceSubscriberInterface
{
    /**
     * A bot legitimately replays several turns in a row (ending a round in
     * President hands the lead back to the same player), so the ceiling can't be
     * the player count. It only exists so a mode that never advances the turn
     * can't spin forever.
     */
    private const int MAX_BOT_TURNS = 50;

    private int $botTurnDepth = 0;

    /**
     * @param iterable<GameModeInterface> $gameModes
     */
    public function __construct(
        private iterable $gameModes,
        private ContainerInterface $container,
        private GameStateProviderInterface $gameStateProvider,
        private GameEventApplier $gea,
        private EventPublisher $publisher,
    ) {
    }

    public static function getSubscribedServices(): array
    {
        return [
            'result_repository' => ResultRepository::class,
            'user_repository' => UserRepository::class,
            'event_dispatcher' => EventDispatcherInterface::class,
            // lazy: GameAI depends on GameManager, so it can't be a constructor arg
            'game_ai' => GameAI::class,
            'logger' => LoggerInterface::class,
        ];
    }

    public function getDefaultConfiguration(GameModeEnum $gameMode): GameConfiguration
    {
        return $this->buildConfiguration($gameMode, []);
    }

    /**
     * @param array<string, mixed> $rawOptions
     */
    public function buildConfiguration(GameModeEnum $gameModeEnum, array $rawOptions): GameConfiguration
    {
        $gameMode = $this->getGameMode($gameModeEnum);

        return new GameConfigurationBuilder()->build($gameMode, $rawOptions);
    }

    public function start(Room $room): GameState
    {
        $gameMode = $this->getGameMode($room->getGameMode()->getValue());
        $deck = $this->createDeck($room->getConfiguration());
        $cards = $deck->cards;
        $roomPlayers = $room->getPlayers();
        [$hands, $drawPile] = $this->drawHands($deck, count($roomPlayers), $gameMode);
        // DrawPile is keyed by card id with id values too (like Hand/DiscardPile),
        // not the Card objects themselves - array_map preserves the id keys here
        $drawPileIds = array_map(fn (Card $card): string => $card->id, $drawPile);

        $players = [];

        foreach ($roomPlayers as $k => $player) {
            $hand = $hands[$k];

            $players[] = new PlayerState(
                $player->id,
                $player->playerName,
                count($hand),
                new Hand(array_map(fn (Card $card): string => $card->id, $hand)),
                $player->isBot,
            );
        }

        $state = new GameState(
            $players,
            [],
            '',
            [],
            new DiscardPile([]),
            new DrawPile($drawPileIds),
            array_combine(array_map(fn (Card $card) => $card->id, $cards), $cards)
        );

        $order = $gameMode->getPlayerOrder($state);

        $state = new GameState(
            $players,
            $order,
            current($order),
            [],
            new DiscardPile([]),
            new DrawPile($drawPileIds),
            array_combine(array_map(fn (Card $card) => $card->id, $cards), $cards)
        );

        if ($gameMode instanceof SetupGameModeInterface) {
            $ctx = $this->createGameContext($state, $room->getConfiguration());
            $gameMode->setup($ctx);

            foreach ($ctx->flushEvents() as $event) {
                $state = $this->gea->apply($event, $state);
            }
        }

        $this->gameStateProvider->save($room->getId()->toString(), $state);

        // the game can open on a bot - nothing else would ever wake it up
        $this->playPendingBotTurns($room, $state);

        return $this->gameStateProvider->get($room->getId()->toString());
    }

    /**
     * @param array<string>        $cards
     * @param array<string, mixed> $data
     */
    public function play(Room $room, User $user, array $cards, array $data = []): void
    {
        $this->playAs($room, $user->getId()->toString(), $cards, $data);
    }

    /**
     * Plays for any player, human or bot - $playerId is a GameState player id.
     *
     * @param array<string>        $cards
     * @param array<string, mixed> $data
     */
    public function playAs(Room $room, string $playerId, array $cards, array $data = []): void
    {
        $state = $this->gameStateProvider->get($room->getId()->toString());
        $player = $this->resolveActingPlayer($state, $playerId);

        $this->assertCanPlay($state, $player);

        if ($state->everyoneCanPlay()) {
            if ($state->currentPlayerId !== $player->id && [] === $cards) {
                return;
            }

            $state = $this->applyEveryoneCanPlayOverride($state, $player);
        }

        $gameMode = $this->getGameMode($room->getGameMode()->getValue());

        $context = new GameContext($state, $room->getConfiguration());
        $gameMode->play($cards, $context, $player->id, $data);
        $events = $context->flushEvents();

        foreach ($events as $event) {
            $state = $this->gea->apply($event, $state);
        }

        $context = new GameContext($state, $room->getConfiguration());
        $gameMode->refreshScore($context);
        $scoreEvents = $context->flushEvents();

        foreach ($scoreEvents as $event) {
            $state = $this->gea->apply($event, $state);
        }

        $events = array_merge($events, $scoreEvents);

        $this->dispatchEvents($events);
        $this->finishGameIfNeeded($room, $player, $state, $gameMode);

        $this->gameStateProvider->save($room->getId()->toString(), $state);

        $this->publisher->publish($room, $events);

        $this->playPendingBotTurns($room, $state);
    }

    /**
     * ponytail: les bots jouent en synchrone dans la requete du joueur precedent.
     * A passer sur Messenger si le service Python devient lent.
     */
    private function playPendingBotTurns(Room $room, GameState $state): void
    {
        if (GameStatusEnum::FINISHED === $room->getStatus()) {
            return;
        }

        $current = $state->players[$state->currentPlayerId] ?? null;

        if (null === $current || !$current->isBot) {
            return;
        }

        // playAs() re-enters here for the next player, so one bot per call is enough
        if (++$this->botTurnDepth > self::MAX_BOT_TURNS) {
            --$this->botTurnDepth;

            $this->container->get('logger')->error('Bot turn chain hit its ceiling', [
                'room' => $room->getId()->toString(),
                'bot' => $current->id,
            ]);

            return;
        }

        try {
            $this->container->get('game_ai')->playAsBot($room, $current, $state);
        } catch (RuleException $e) {
            // the human's move is already saved - a bot picking an illegal card
            // must not blow up their request. Stop the chain and leave a trace.
            $this->container->get('logger')->error('Bot played an illegal move', [
                'room' => $room->getId()->toString(),
                'bot' => $current->id,
                'exception' => $e,
            ]);
        } finally {
            --$this->botTurnDepth;
        }
    }

    private function getGameMode(GameModeEnum $gameModeEnum): GameModeInterface
    {
        foreach ($this->gameModes as $gameMode) {
            if ($gameMode->getGameMode() === $gameModeEnum) {
                return $gameMode;
            }
        }

        throw new \InvalidArgumentException('Game mode not found');
    }

    private function resolveActingPlayer(GameState $state, string $id): PlayerState
    {
        return current(array_filter(
            $state->players,
            fn (PlayerState $p): bool => $p->id === $id,
        ));
    }

    private function assertCanPlay(GameState $state, PlayerState $player): void
    {
        if (!$state->everyoneCanPlay() && $state->currentPlayerId !== $player->id) {
            throw new \InvalidArgumentException('Not your turn');
        }
    }

    private function applyEveryoneCanPlayOverride(GameState $state, PlayerState $player): GameState
    {
        return $state->withCurrentPlayer($player->id);
    }

    /**
     * @param GameEvent[] $events
     */
    private function dispatchEvents(array $events): void
    {
        foreach ($events as $event) {
            $this->container->get('event_dispatcher')->dispatch($event);
        }
    }

    private function finishGameIfNeeded(Room $room, PlayerState $player, GameState $state, GameModeInterface $gameMode): void
    {
        if (!$gameMode->isGameFinished($state)) {
            return;
        }

        $room->setStatus(GameStatusEnum::FINISHED);

        // dispatched for bots too - this is what tells the front the game is over
        $this->container->get('event_dispatcher')->dispatch(new GameFinishedEvent($room, $state, $player));

        if ($player->isBot) {
            // no User row behind a bot, so nothing to record in the leaderboard
            return;
        }

        $winner = $this->container->get('user_repository')->find($player->id);

        $this->container->get('result_repository')->save(new Result($winner, $room));
    }

    private function createDeck(GameConfiguration $config): Deck
    {
        $deck = new DeckBuilder();

        if ($config->hasJokers()) {
            $deck->withJokers();
        }

        if ($config->deckCount() > 1) {
            $deck->withDeckCount($config->deckCount());
        }

        return $deck->build()->shuffle();
    }

    /**
     * @return array{
     *    0: array<int, Card[]>,
     *    1: Card[],
     * }
     */
    private function drawHands(Deck $deck, int $count, GameModeInterface $gameMode): array
    {
        $cardPerPlayer = $gameMode->getCardsCount($count);

        if (null === $cardPerPlayer) {
            $baseCards = intdiv(count($deck->cards), $count);
            $remainder = count($deck->cards) % $count;

            $cardsPerPlayer = array_fill(0, $count, $baseCards);
            for ($i = 0; $i < $remainder; ++$i) {
                ++$cardsPerPlayer[$i];
            }
        } else {
            $cardsPerPlayer = array_fill(0, $count, $cardPerPlayer);
        }

        $remainingCards = $deck->cards;
        $hands = [];

        foreach ($cardsPerPlayer as $count) {
            $hands[] = array_splice($remainingCards, 0, $count);
        }

        return [$hands, $remainingCards];
    }

    private function createGameContext(GameState $state, GameConfiguration $configuration): GameContext
    {
        return new GameContext($state, $configuration);
    }
}
