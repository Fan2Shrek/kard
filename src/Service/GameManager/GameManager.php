<?php

namespace App\Service\GameManager;

use App\Domain\Exception\RuleException;
use App\Entity\Room;
use App\Entity\User;
use App\Model\Card\Card;
use App\Model\GameContext;
use App\Service\Card\HandRepository;
use App\Service\GameContextProvider;
use App\Service\GameManager\GameMode\GameModeEnum;
use App\Service\GameManager\GameMode\GameModeInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Serializer\SerializerInterface;
use Twig\Environment;

final class GameManager
{
    // @param iterable<GameModeInterface> $gameModes
    public function __construct(
        private iterable $gameModes,
        private HubInterface $hub,
        private GameContextProvider $gameContextProvider,
        private HandRepository $handRepository,
        private SerializerInterface $serializer,
        private Environment $twig,
    ) {}

    /**
     * @param array<Card> $cards
     */
    public function play(Room $room, User $player, array $cards): void
    {
        $ctx = $this->gameContextProvider->provide($room);

        if ($ctx->getCurrentPlayer()->id !== $player->getId()->toString()) {
            throw new \InvalidArgumentException('Not your turn');
        }

        $hand = $this->handRepository->get($player, $room);

        if (!empty($cards) && !$hand->hasCards($cards)) {
            throw new \InvalidArgumentException('Card not found in player hand');
        }

        /* @todo */
        /* $gameMode = $this->getGameMode($room->getGameMode());  */
        $gameMode = $this->getGameMode(GameModeEnum::PRESIDENT);

        try {
            $gameMode->play($cards, $ctx);
        } catch (RuleException $e) {
            /** @todo do something */
            throw $e;
            return;
        }

        $hand->removeCards($cards);

        $this->handRepository->save($player, $room, $hand);
        $this->gameContextProvider->save($ctx);

        $this->hub->publish(new Update(
            sprintf('room-%s', $room->getId()),
            $this->serializer->serialize([
                'action' => 'play',
                'data' => $ctx,
            ], 'json'),
        ));

        $this->hub->publish(new Update(
            sprintf('room-%s-%s', $room->getId(), $player->getId()),
            $this->serializer->serialize($hand, 'json'),
        ));
    }

    public function start(GameContext $ctx): void
    {
        $players = $ctx->getPlayers();

        /* $players = $this->getGameMode($room->getGameMode())->getPlayerOrder($players); */
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
            function ($acc, $player) use ($ctx) {
                $acc[$player->id] = $player;

                return $acc;
            },
            [],
        );

        /* $order = $this->getGameMode($ctx->getRoom()->getGameMode())->getPlayerOrder($players); */
        $order = $this->getGameMode(GameModeEnum::PRESIDENT)->getPlayerOrder($hands);

        $ctx->setPlayerOrder(
            array_map(
                function ($id) use ($players) {
                    return $players[$id];
                },
                $order,
            ),
        );
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

    public function getGameModes(): iterable
    {
        return $this->gameModes;
    }

    private function renderView(string $template, array $data): string
    {
        return $this->twig->render($template, $data);
    }
}
