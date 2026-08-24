<?php

namespace App\Controller;

use App\Entity\GameModeDescription;
use App\Entity\Room;
use App\Enum\GameStatusEnum;
use App\Event\Room\RoomEvent;
use App\Repository\GameModeDescriptionRepository;
use App\Repository\GameModeRepository;
use App\Repository\RoomRepository;
use App\Service\AssetsProvider;
use App\Game\GameManager;
use App\Game\Mode\GameModeEnum;
use App\Game\Model\Card\Hand;
use App\Game\Model\State\PlayerState;
use App\Game\StateProvider\GameStateProviderInterface;
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

            $this->hub->publish(new Update(
                'current_games',
                $this->renderView('components/turbo/game-details.html.twig', ['game' => $room])
            ));

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
        if (GameStatusEnum::PLAYING === $room->getStatus()) {
            return $this->redirectToRoute('game', ['id' => $room->getId()]);
        }

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
                \sprintf('game-%s-waiting', $room->getId()),
                $this->renderView('components/turbo/player-join.html.twig', [
                    'player' => new PlayerState(
						$user->getId()->toString(),
						$user->getUsername(),
						0,
						new Hand([]),
					),
                ])
            ));
        }

        $players = array_map(
            fn ($player): PlayerState => new PlayerState(
				$player->getId()->toString(),
				$player->getUsername(),
				0,
				new Hand([]),
			),
            $room->getParticipants()->toArray(),
        );

        return $this->render('home/waiting.html.twig', [
            'room' => $room,
            'players' => $players,
        ]);
    }

    #[Route('/leave/{id}', name: 'game_leave')]
    public function leave(Room $room): Response
    {
        if (GameStatusEnum::PLAYING === $room->getStatus()) {
            return $this->redirectToRoute('game', ['id' => $room->getId()]);
        }

        $user = $this->getUser();
        $isInGame = false;
        foreach ($room->getParticipants() as $player) {
            if ($player->getUsername() === $user->getUsername()) {
                $isInGame = true;
                break;
            }
        }

        if ($isInGame) {
            if ($room->getOwner() === $user) {
                $this->hub->publish(new Update(
                    sprintf('game-%s', $room->getId()),
                    json_encode([
                        'url' => $this->generateUrl('home'),
                    ])
                ));

                $id = $room->getId()->toString();
                $this->roomRepository->remove($room);

                $this->hub->publish(new Update(
                    'current_games',
                    "<turbo-stream action=\"remove\" target=\"game-{$id}\"></turbo-stream>"
                ));

                return $this->redirectToRoute('home');
            }

            $room->removeParticipantBlaBlaBla($user);
            $this->roomRepository->save($room);

            $this->hub->publish(new Update(
                \sprintf('game-%s-waiting', $room->getId()),
                "<turbo-stream action=\"remove\" target=\"player-{$user->getId()}\"></turbo-stream>"
            ));
        }

        return $this->redirectToRoute('home');
    }

    #[Route('/start/{id}', name: 'game_start')]
    public function start(Room $room): Response
    {
        $response = $this->redirectToRoute('game', ['id' => $room->getId()]);
        $room->setStatus(GameStatusEnum::PLAYING);

        $gameContext = $this->gameManager->start($room);
        $this->roomRepository->save($room);

        $this->hub->publish(new Update(
            'current_games',
            "<turbo-stream action=\"remove\" target=\"game-{$room->getId()}\"></turbo-stream>"
        ));

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
        GameStateProviderInterface $gameStateProvider,
    ): Response {
        $user = $this->getUser();

		$state = $gameStateProvider->get($room->getId()->toString());
		$assets = $assetsProvider->getAssets($state->cards);


        if (!\in_array($user, $room->getParticipants()->toArray(), true)) {
            return $this->render('home/game.html.twig', [
                'assets' => $assets,
                'game' => $serializer->serialize($state, 'json'),
                'room' => $room,
            ]);
        }

        return $this->render('home/game.html.twig', [
            'assets' => $assets,
            'game' => $serializer->serialize($state, 'json'),
            'player' => $serializer->serialize($this->getUser(), 'json'),
            'playerId' => $user->getId(),
            'room' => $room,
        ]);
    }
}
