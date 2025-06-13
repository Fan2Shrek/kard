<?php

namespace App\Service\GameManager;

use App\Domain\Exception\RuleException;
use App\Entity\Result;
use App\Entity\Room;
use App\Entity\User;
use App\Enum\GameStatusEnum;
use App\Model\Card\Card;
use App\Model\Card\Hand;
use App\Model\GameContext;
use App\Model\Player;
use App\Repository\ResultRepository;
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

        foreach ($room->getPlayers() as $k => $player) {
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
            function ($acc, $player) use ($ctx) {
                $acc[$player->id] = $this->handRepository->get($player->id, $ctx->getRoom());

                return $acc;
            },
            [],
        );

        $players = array_reduce(
            $players,
            function ($acc, $player) {
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
    }

    /**
     * @param array<Card>          $cards
     * @param array<string, mixed> $data
     */
    public function play(Room $room, User $user, array $cards, array $data = []): void
    {
        $room->setStatus(GameStatusEnum::PLAYING);
        $ctx = $this->gameContextProvider->provide($room);

        if ($ctx->getCurrentPlayer()->id !== $user->getId()->toString()) {
            throw new \InvalidArgumentException('Not your turn');
        }

        $hand = $this->handRepository->get($user, $room);

        if (!empty($cards) && !$hand->hasCards($cards)) {
            throw new \InvalidArgumentException('Card not found in player hand');
        }

        $gameMode = $this->getGameMode($room->getGameMode()->getValue());

        try {
            $gameMode->play($cards, $ctx, $hand, $data);
        } catch (RuleException $e) {
            /* @todo do something */
            throw $e;
            /* return; */
        }

        $this->handRepository->save($user, $room, $hand);

        $player = current(array_filter(
            $ctx->getPlayers(),
            fn (Player $p) => $p->id === $user->getId()->toString(),
        ));
        $player->cardsCount = count($hand);

        $this->gameContextProvider->save($ctx);

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

            $result = new Result($user, $room->getGameMode());
            $this->container->get('result_repository')->save($result);

            return;
        }

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
            count($room->getPlayers()),
            $gameMode->getCardsCount(count($room->getPlayers())) ?: 0,
        );
    }
}
