<?php

namespace App\Controller;

use App\Entity\GameModeDescription;
use App\Entity\Room;
use App\Entity\User;
use App\Model\Player;
use App\Repository\GameModeDescriptionRepository;
use App\Repository\GameModeRepository;
use App\Repository\RoomRepository;
use App\Service\Card\CardGenerator;
use App\Service\Card\HandRepository;
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

/** @todo serviceSubscriber for perfomance optimisation */
#[Route('/room')]
final class RoomController extends AbstractController
{
    public function __construct(
        private RoomRepository $roomRepository,
        private CardGenerator $cardGenerator,
        private SerializerInterface $serializer,
        private HandRepository $handRepository,
        private GameContextProvider $gameContextProvider,
        private GameManager $gameManager,
        private HubInterface $hub,
        private GameModeRepository $gameModeRepository,
        private GameModeDescriptionRepository $gameModeDescriptionRepository,
    ) {
    }

    #[Route('/create', name: 'create')]
    public function create(Request $request): Response
    {
        if (Request::METHOD_POST === $request->getMethod()) {
            $gameMode = $request->getPayload()->get('gameMode');
            $gameMode = $this->gameModeRepository->findByGameMode(GameModeEnum::from($gameMode));

            $user = $this->getUser();
            $room = new Room($gameMode);
            $room->setOwner($user);
            $room->addPlayer($user);

            $this->roomRepository->save($room);

            return $this->redirectToRoute('waiting', ['id' => $room->getId()]);
        }

        $gameModes = $this->gameModeRepository->findActiveGameModes();
        $descriptions = $this->gameModeDescriptionRepository->findAllByGameMode($gameModes);

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
        foreach ($room->getPlayers() as $player) {
            if ($player->getUsername() === $user->getUsername()) {
                $hasJoined = true;
                break;
            }
        }

        if (!$hasJoined) {
            $room->addPlayer($user);
            $this->roomRepository->save($room);

            $this->hub->publish(new Update(
                'waiting',
                $this->renderView('components/turbo/player-join.html.twig', [
                    'player' => Player::fromUser($user),
                ])
            ));
        }

        $players = array_map(
            fn ($player) => Player::fromUser($player),
            $room->getPlayers()->toArray(),
        );

        return $this->render('home/waiting.html.twig', [
            'room' => $room,
            'players' => $players,
        ]);
    }

    #[Route('/start/{id}', name: 'game_start')]
    public function start(Room $room): Response
    {
        $playerCount = count($room->getPlayers());
        $hands = $this->cardGenerator->generateHands($playerCount);
        $response = $this->redirectToRoute('game', ['id' => $room->getId()]);

        $gameContext = $this->gameContextProvider->provide($room);
        $players = array_reduce($gameContext->getPlayers(), function (array $carry, Player $player) {
            $carry[$player->id] = $player;

            return $carry;
        }, []);

        foreach ($room->getPlayers() as $k => $player) {
            $this->handRepository->save($player, $room, $hands[$k]);
            $players[$player->getId()->toString()]->cardsCount = count($hands[$k]);
        }

        $this->gameManager->start($gameContext);
        $this->roomRepository->save($room);
        $this->gameContextProvider->save($gameContext);

        $this->hub->publish(new Update(
            sprintf('game-%s', $room->getId()),
            json_encode([
                'url' => $response->getTargetUrl(),
            ])
        ));

        return $response;
    }

    #[Route('/game/{id}', name: 'game')]
    public function game(Room $room): Response
    {
        $user = $this->getUser();

        return $this->render('home/game.html.twig', [
            'game' => $this->serializer->serialize($this->gameContextProvider->provide($room), 'json'),
            'player' => $this->serializer->serialize($this->getUser(), 'json'),
            'hand' => $this->handRepository->get($user, $room)->getCards(),
            'playerId' => $user->getId(),
            'room' => $room,
        ]);
    }

    protected function getUser(): User
    {
        $user = parent::getUser();

        if (!$user instanceof User) {
            throw new \LogicException('User must be an instance of User');
        }

        return $user;
    }
}
