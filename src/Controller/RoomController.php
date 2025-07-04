<?php

namespace App\Controller;

use App\Entity\GameModeDescription;
use App\Entity\Room;
use App\Enum\GameStatusEnum;
use App\Event\Room\RoomEvent;
use App\Model\Player;
use App\Repository\GameModeDescriptionRepository;
use App\Repository\GameModeRepository;
use App\Repository\RoomRepository;
use App\Service\AssetsProvider;
use App\Service\Card\HandRepositoryInterface;
use App\Service\GameContextProvider;
use App\Service\GameManager\GameManager;
use App\Service\GameManager\GameMode\GameModeEnum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route('/room')]
final class RoomController extends AbstractController
{
    use ControllerTrait;

    public function __construct(
        private RoomRepository $roomRepository,
        private GameManager $gameManager,
        private HubInterface $hub,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    #[Route('/create', name: 'create')]
    public function create(
        Request $request,
        GameModeRepository $gameModeRepository,
        GameModeDescriptionRepository $gameModeDescriptionRepository,
    ): Response {
        if (Request::METHOD_POST === $request->getMethod()) {
            $gameMode = $request->getPayload()->get('gameMode');
            $gameMode = $gameModeRepository->findByGameMode(GameModeEnum::from($gameMode));

            $user = $this->getUser();
            $room = new Room($gameMode);
            $room->setOwner($user);
            $room->addParticipant($user);

            $this->roomRepository->save($room);

            $this->eventDispatcher->dispatch(new RoomEvent($room), 'room.created');

            return $this->redirectToRoute('waiting', ['id' => $room->getId()]);
        }

        $gameModes = $gameModeRepository->findActiveGameModes();
        $descriptions = $gameModeDescriptionRepository->findAllByGameMode($gameModes);

        return $this->render('home/create.html.twig', [
            'gameModes' => $gameModes,
            'descriptions' => array_reduce($descriptions, function (array $acc, GameModeDescription $description) {
                $acc[$description->getGameMode()->getId()] = $description;

                return $acc;
            }, []),
        ]);
    }

    #[Route('/waiting/{id}', name: 'waiting')]
    public function waiting(Room $room): Response
    {
        $user = $this->getUser();
        $hasJoined = false;
        foreach ($room->getParticipants() as $player) {
            if ($player->getUsername() === $user->getUsername()) {
                $hasJoined = true;
                break;
            }
        }

        if (!$hasJoined) {
            $room->addParticipant($user);
            $this->roomRepository->save($room);

            $this->hub->publish(new Update(
                'waiting',
                $this->renderView('components/turbo/player-join.html.twig', [
                    'player' => Player::fromUser($user),
                ])
            ));
        }

        $players = array_map(
            fn ($player): Player => Player::fromUser($player),
            $room->getParticipants()->toArray(),
        );

        return $this->render('home/waiting.html.twig', [
            'room' => $room,
            'players' => $players,
        ]);
    }

    #[Route('/start/{id}', name: 'game_start')]
    public function start(Room $room): Response
    {
        $response = $this->redirectToRoute('game', ['id' => $room->getId()]);
        $room->setStatus(GameStatusEnum::PLAYING);

        $gameContext = $this->gameManager->setupRoom($room);

        $this->gameManager->start($gameContext);
        $this->roomRepository->save($room);

        $this->hub->publish(new Update(
            sprintf('game-%s', $room->getId()),
            json_encode([
                'url' => $response->getTargetUrl(),
            ])
        ));

        return $response;
    }

    #[Route('/game/{id}', name: 'game')]
    public function game(
        Room $room,
        SerializerInterface $serializer,
        AssetsProvider $assetsProvider,
        GameContextProvider $gameContextProvider,
        HandRepositoryInterface $handRepository,
    ): Response {
        $user = $this->getUser();

        return $this->render('home/game.html.twig', [
            'assets' => $assetsProvider->getAllCardsAssets(),
            'game' => $serializer->serialize($gameContextProvider->provide($room), 'json'),
            'player' => $serializer->serialize($this->getUser(), 'json'),
            'hand' => $handRepository->get($user, $room)->getCards(),
            'playerId' => $user->getId(),
            'room' => $room,
        ]);
    }
}
