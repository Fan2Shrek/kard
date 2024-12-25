<?php

namespace App\Controller;


use App\Entity\Room;
use App\Entity\User;
use App\Model\Player;
use App\Repository\RoomRepository;
use App\Service\Card\CardGenerator;
use App\Service\Card\HandRepository;
use App\Service\GameContextProvider;
use App\Service\GameManager\GameManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

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
    ) {
    }

    #[Route('/create', name: 'create')]
    public function create(): Response
    {
        $user = $this->getUser();
        $room = new Room();
        $room->setOwner($user);
        $room->addPlayer($user);

        $this->roomRepository->save($room);

        return $this->redirectToRoute('waiting', ['id' => $room->getId()]);
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
                    'player' =>  Player::fromUser($user),
                ])
            ));
        }

        $players =  array_map(
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

        foreach ($room->getPlayers() as $k => $player) {
            $this->handRepository->save($player, $room, $hands[$k]);
        }

        $gameContext = $this->gameContextProvider->provide($room);
        $this->gameManager->start($gameContext);

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
