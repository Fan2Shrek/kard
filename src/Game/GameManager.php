<?php

namespace App\Game;

use App\Entity\Result;
use App\Entity\Room;
use App\Enum\GameStatusEnum;
use App\Game\Card\CachedHandRepositoryInterface;
use App\Game\Card\CardGenerator;
use App\Game\Card\HandRepositoryInterface;
use App\Game\Event\GameEventApplierInterface;
use App\Game\Event\GameFinishedEvent;
use App\Game\Mode\GameModeEnum;
use App\Game\Mode\GameModeInterface;
use App\Game\Mode\SetupGameModeInterface;
use App\Game\Model\Card\Card;
use App\Game\Model\Card\Hand;
use App\Game\Model\GameContext;
use App\Game\Model\GameState;
use App\Game\Model\Player;
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
        private GameStateProvider $gameStateProvider,
        private HandRepositoryInterface $handRepository,
        private GameEventApplierInterface $applier,
    ) {
    }

    public static function getSubscribedServices(): array
    {
        return [
            'result_repository' => ResultRepository::class,
            'card_generator' => CardGenerator::class,
            'user_repository' => UserRepository::class,
            'event_dispatcher' => EventDispatcherInterface::class,
        ];
    }

    public function setupRoom(Room $room): GameState
    {
        [$hands, $drawPile] = $this->drawHands($room);

        $gameContext = $this->gameStateProvider->provide($room);
        $gameContext = $gameContext->withDrawPile($drawPile);

        foreach ($room->getParticipants() as $k => $player) {
            $this->handRepository->save($player, $room, $hands[$k]);

            $currentPlayer = current(array_filter(
                $gameContext->getPlayers(),
                fn (Player $p): bool => $p->id === $player->getId()->toString(),
            ));
            $gameContext = $gameContext->withUpdatedPlayer($currentPlayer->withCardsCount(count($hands[$k])));
        }

        $this->gameStateProvider->save($gameContext);

        return $gameContext;
    }

    public function start(GameState $ctx): void
    {
        $players = $ctx->getPlayers();

        $hands = array_reduce(
            $players,
            function (array $acc, $player) use ($ctx) {
                $acc[$player->id] = $this->handRepository->get($player->id, $ctx->getRoom());

                return $acc;
            },
            [],
        );

        $players = array_reduce(
            $players,
            function (array $acc, $player) {
                $acc[$player->id] = $player;

                return $acc;
            },
            [],
        );

        $gameMode = $this->getGameMode($ctx->getRoom()->getGameMode()->getValue());
        $order = $gameMode->getPlayerOrder($hands);

        if ($gameMode instanceof SetupGameModeInterface) {
            $gameMode->setup($ctx, $hands);
        }

        $ctx = $ctx->withPlayerOrder(
            array_map(
                function ($id) use ($players) {
                    return $players[$id];
                },
                $order,
            ),
        );

        $this->gameStateProvider->save($ctx);
    }

    /**
     * @param array<Card>          $cards
     * @param array<string, mixed> $data
     */
    public function play(Room $room, Player $player, array $cards, array $data = []): void
    {
        $ctx = $this->gameStateProvider->provide($room);
        $player = $this->resolveActingPlayer($ctx, $player);

        $this->assertCanPlay($ctx, $player);

        if ($ctx->isFastPlay()) {
            if ($ctx->getCurrentPlayer()->id !== $player->id && [] === $cards) {
                return;
            }

            $ctx = $this->applyFastPlayOverride($ctx, $player);
        }

        $hand = $this->loadAndValidateHand($room, $player, $cards);

        $gameMode = $this->getGameMode($room->getGameMode()->getValue());

        $context = new GameContext($ctx, $this->applier);
        $gameMode->play($cards, $context, $hand, $data);
        $ctx = $context->getState();

        $ctx = $this->persistPlayerState($room, $player, $hand, $ctx);
        $this->dispatchEvents($context);
        $this->finishGameIfNeeded($room, $player, $ctx, $gameMode);
    }

    public function getGameMode(GameModeEnum $gameModeEnum): GameModeInterface
    {
        foreach ($this->gameModes as $gameMode) {
            if ($gameMode->getGameMode() === $gameModeEnum) {
                return $gameMode;
            }
        }

        throw new \InvalidArgumentException('Game mode not found');
    }

    private function resolveActingPlayer(GameState $ctx, Player $player): Player
    {
        return current(array_filter(
            $ctx->getPlayers(),
            fn (Player $p): bool => $p->id === $player->id,
        ));
    }

    private function assertCanPlay(GameState $ctx, Player $player): void
    {
        if (!$ctx->isFastPlay() && $ctx->getCurrentPlayer()->id !== $player->id) {
            throw new \InvalidArgumentException('Not your turn');
        }
    }

    private function applyFastPlayOverride(GameState $ctx, Player $player): GameState
    {
        return $ctx->withCurrentPlayer(
            current(array_filter(
                $ctx->getPlayers(),
                fn (Player $p): bool => $p->id === $player->id,
            )),
        );
    }

    /**
     * @param array<Card> $cards
     */
    private function loadAndValidateHand(Room $room, Player $player, array $cards): Hand
    {
        $hand = $this->handRepository->get($player->id, $room);

        if (!empty($cards) && !$hand->hasCards($cards)) {
            throw new \InvalidArgumentException('Card not found in player hand');
        }

        return $hand;
    }

    private function persistPlayerState(Room $room, Player $player, Hand $hand, GameState $ctx): GameState
    {
        $this->handRepository->save($player->id, $room, $hand);

        $ctx = $ctx->withUpdatedPlayer($player->withCardsCount(count($hand)));

        $this->gameStateProvider->save($ctx);

        return $ctx;
    }

    private function dispatchEvents(GameContext $context): void
    {
        foreach ($context->flushEvents() as $event) {
            $this->container->get('event_dispatcher')->dispatch($event);
        }
    }

    private function finishGameIfNeeded(Room $room, Player $player, GameState $ctx, GameModeInterface $gameMode): void
    {
        if (!$gameMode->isGameFinished($ctx)) {
            return;
        }

        $room->setStatus(GameStatusEnum::FINISHED);

        $result = new Result(
            $this->container->get('user_repository')->find($player->id),
            $room,
        );
        if ($this->handRepository instanceof CachedHandRepositoryInterface) {
            $this->handRepository->deleteAllHandForRoom($room);
        }
        $this->gameStateProvider->clear($room);

        $this->container->get('event_dispatcher')->dispatch(new GameFinishedEvent($room, $ctx));
        $this->container->get('result_repository')->save($result);
    }

    /**
     * @return array{
     *    0: Hand[],
     *    1: Card[],
     * }
     */
    private function drawHands(Room $room): array
    {
        $gameMode = $this->getGameMode($room->getGameMode()->getValue());

        return $this->container->get('card_generator')->generateHands(
            count($room->getParticipants()),
            $gameMode->getCardsCount(count($room->getParticipants())) ?: 0,
        );
    }
}
