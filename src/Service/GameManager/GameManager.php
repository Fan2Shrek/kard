<?php

namespace App\Service\GameManager;

use App\Entity\Result;
use App\Entity\Room;
use App\Enum\GameStatusEnum;
use App\Model\Card\Card;
use App\Model\Card\Hand;
use App\Model\GameContext;
use App\Model\Player;
use App\Repository\ResultRepository;
use App\Repository\UserRepository;
use App\Service\Card\CardGenerator;
use App\Service\Card\HandRepositoryInterface;
use App\Service\GameContextProvider;
use App\Service\GameManager\GameMode\GameModeEnum;
use App\Service\GameManager\GameMode\GameModeInterface;
use App\Service\GameManager\GameMode\SetupGameModeInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\SerializerInterface;
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
        private GameContextProvider $gameContextProvider,
        private HandRepositoryInterface $handRepository,
        private SerializerInterface $serializer,
    ) {
    }

    public static function getSubscribedServices(): array
    {
        return [
            'router' => RouterInterface::class,
            'result_repository' => ResultRepository::class,
            'card_generator' => CardGenerator::class,
            'user_repository' => UserRepository::class,
        ];
    }

    public function setupRoom(Room $room): GameContext
    {
        [$hands, $drawPile] = $this->drawHands($room);

        $gameContext = $this->gameContextProvider->provide($room);
        $gameContext->setDrawPile($drawPile);

        $players = array_reduce($gameContext->getPlayers(), function (array $carry, Player $player) {
            $carry[$player->id] = $player;

            return $carry;
        }, []);

        foreach ($room->getParticipants() as $k => $player) {
            $this->handRepository->save($player, $room, $hands[$k]);
            $players[$player->getId()->toString()]->cardsCount = count($hands[$k]);
        }

        $this->gameContextProvider->save($gameContext);

        return $gameContext;
    }

    public function start(GameContext $ctx): void
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

        $this->gameContextProvider->save($ctx);
    }

    /**
     * @param array<Card>          $cards
     * @param array<string, mixed> $data
     */
    public function play(Room $room, Player $player, array $cards, array $data = []): void
    {
        $ctx = $this->gameContextProvider->provide($room);
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

        $this->gameContextProvider->save($ctx);

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
            $this->hub->publish(new Update(
                sprintf('room-%s', $room->getId()),
                $this->serializer->serialize([
                    'action' => 'end',
                    'data' => [
                        'context' => $ctx,
                        'url' => $this->container->get('router')->generate('home'),
                    ],
                ], 'json'),
            ));

            $room->setStatus(GameStatusEnum::FINISHED);

            $result = new Result(
                $this->container->get('user_repository')->find($player->id),
                $room->getGameMode()
            );
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
