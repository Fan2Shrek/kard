<?php

namespace App\Game;

use App\Entity\Result;
use App\Entity\Room;
use App\Enum\GameStatusEnum;
use App\Game\Card\CachedHandRepositoryInterface;
use App\Game\Card\CardGenerator;
use App\Game\Card\HandRepositoryInterface;
use App\Game\Event\GameFinishedEvent;
use App\Game\Mode\GameModeEnum;
use App\Game\Mode\GameModeInterface;
use App\Game\Mode\SetupGameModeInterface;
use App\Game\Model\Card\Card;
use App\Game\Model\Card\Hand;
use App\Game\Model\GameState;
use App\Game\Model\Player;
use App\Repository\ResultRepository;
use App\Repository\UserRepository;
use Psr\Container\ContainerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Serializer\SerializerInterface;
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
        private HubInterface $hub,
        private GameStateProvider $gameStateProvider,
        private HandRepositoryInterface $handRepository,
        private SerializerInterface $serializer,
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
        $gameContext->setDrawPile($drawPile);

        $players = array_reduce($gameContext->getPlayers(), function (array $carry, Player $player) {
            $carry[$player->id] = $player;

            return $carry;
        }, []);

        foreach ($room->getParticipants() as $k => $player) {
            $this->handRepository->save($player, $room, $hands[$k]);
            $players[$player->getId()->toString()]->cardsCount = count($hands[$k]);
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

        $ctx->setPlayerOrder(
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
        $player = current(array_filter(
            $ctx->getPlayers(),
            fn (Player $p): bool => $p->id === $player->id,
        ));

        if (!$ctx->getData('fastPlay') && $ctx->getCurrentPlayer()->id !== $player->id) {
            throw new \InvalidArgumentException('Not your turn');
        }

        if ($ctx->getData('fastPlay')) {
            if ($ctx->getCurrentPlayer()->id !== $player->id && [] === $cards) {
                return;
            }

            $ctx->setCurrentPlayer(
                current(array_filter(
                    $ctx->getPlayers(),
                    fn (Player $p): bool => $p->id === $player->id,
                )),
            );
        }

        $hand = $this->handRepository->get($player->id, $room);

        if (!empty($cards) && !$hand->hasCards($cards)) {
            throw new \InvalidArgumentException('Card not found in player hand');
        }

        $gameMode = $this->getGameMode($room->getGameMode()->getValue());

        $gameMode->play($cards, $ctx, $hand, $data);

        $this->handRepository->save($player->id, $room, $hand);

        $player->cardsCount = count($hand);

        $this->gameStateProvider->save($ctx);

        $this->hub->publish(new Update(
            sprintf('room-%s', $room->getId()),
            $this->serializer->serialize([
                'action' => 'play',
                'data' => $ctx,
            ], 'json'),
        ));

        $this->hub->publish(new Update(
            sprintf('room-%s-%s', $room->getId(), $player->id),
            $this->serializer->serialize($hand, 'json'),
        ));

        if ($gameMode->isGameFinished($ctx)) {
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

            return;
        }
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
