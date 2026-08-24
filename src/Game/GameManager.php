<?php

namespace App\Game;

use App\Entity\Result;
use App\Entity\Room;
use App\Entity\User;
use App\Enum\GameStatusEnum;
use App\Game\Builder\DeckBuilder;
use App\Game\Event\GameFinishedEvent;
use App\Game\Mode\GameModeEnum;
use App\Game\Mode\GameModeInterface;
use App\Game\Mode\SetupGameModeInterface;
use App\Game\Model\Card\Card;
use App\Game\Model\Card\Deck;
use App\Game\Model\Card\DiscardPile;
use App\Game\Model\Card\DrawPile;
use App\Game\Model\Card\Hand;
use App\Game\Model\Event\GameEvent;
use App\Game\Model\GameContext;
use App\Game\Model\State\GameState;
use App\Game\Model\State\PlayerState;
use App\Game\Service\GameEventApplier;
use App\Game\StateProvider\GameStateProviderInterface;
use App\Repository\ResultRepository;
use App\Repository\UserRepository;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

final class GameManager implements ServiceSubscriberInterface
{
    /**
     * @param iterable<GameModeInterface> $gameModes
     */
    public function __construct(
        private iterable $gameModes,
        private ContainerInterface $container,
        private GameStateProviderInterface $gameStateProvider,
        private GameEventApplier $gea,
    ) {
    }

    public static function getSubscribedServices(): array
    {
        return [
            'result_repository' => ResultRepository::class,
            'user_repository' => UserRepository::class,
            'event_dispatcher' => EventDispatcherInterface::class,
        ];
    }

    public function start(Room $room): GameState
    {
        $gameMode = $this->getGameMode($room->getGameMode()->getValue());
		$deck = $this->createDeck();
		$cards = $deck->cards;
        [$hands, $drawPile] = $this->drawHands($deck, $room->getParticipants()->count(), $gameMode);

		$players = [];

		foreach ($room->getParticipants() as $k => $player) {
			$hand = $hands[$k];

			$players[] = new PlayerState(
				$player->getId()->toString(),
				$player->getUsername(),
				count($hand),
				new Hand(array_map(fn (Card $card): string => $card->id, $hand)),
			);
		}

		$state = new GameState(
			$players,
			[],
			'',
			[],
			new DiscardPile([]),
			new DrawPile($drawPile),
			array_combine(array_map(fn (Card $card) => $card->id, $cards), $cards)
		);

        $order = $gameMode->getPlayerOrder($state);

		$state = new GameState(
			$players,
			$order,
			current($order),
			[],
			new DiscardPile([]),
			new DrawPile($drawPile),
			array_combine(array_map(fn (Card $card) => $card->id, $cards), $cards)
		);


        if ($gameMode instanceof SetupGameModeInterface) {
			$ctx = $this->createGameContext($state);
            $gameMode->setup($ctx);

			foreach ($ctx->flushEvents() as $event) {
				$state = $this->gea->apply($event, $state);
			}
        }

		$this->gameStateProvider->save($room->getId()->toString(), $state);

        return $state;
    }

    /**
     * @param array<string>        $cards
     * @param array<string, mixed> $data
     */
    public function play(Room $room, User $user, array $cards, array $data = []): void
    {
		$state = $this->gameStateProvider->get($room->getId()->toString());
        $player = $this->resolveActingPlayer($state, $user->getId()->toString());

        $this->assertCanPlay($state, $player);

        if ($state->everyoneCanPlay()) {
            if ($state->currentPlayerId !== $player->id && [] === $cards) {
                return;
            }

            $state = $this->applyEveryoneCanPlayOverride($state, $player);
        }

        $gameMode = $this->getGameMode($room->getGameMode()->getValue());

        $context = new GameContext($state);
        $gameMode->play($cards, $context, $player->id, $data);
        $events = $context->flushEvents();

		foreach ($events as $event) {
			$state = $this->gea->apply($event, $state);
		}

		$context = new GameContext($state);
		$gameMode->refreshScore($context);
		$scoreEvents = $context->flushEvents();

		foreach ($scoreEvents as $event) {
			$state = $this->gea->apply($event, $state);
		}

		$events = array_merge($events, $scoreEvents);

        $this->dispatchEvents($events);
        $this->finishGameIfNeeded($room, $player, $state, $gameMode);

		$this->gameStateProvider->save($room->getId()->toString(), $state);
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

        $result = new Result(
            $this->container->get('user_repository')->find($player->id),
            $room,
        );

        $this->container->get('event_dispatcher')->dispatch(new GameFinishedEvent($room, $state));
        $this->container->get('result_repository')->save($result);
    }

	private function createDeck(): Deck
	{
		$deck = new DeckBuilder()->build();

		return $deck->shuffle();
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

	private function createGameContext(GameState $state): GameContext
	{
		return new GameContext($state);
	}
}
