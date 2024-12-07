<?php

namespace App\Controller;

use App\Entity\Room;
use App\Entity\User;
use App\Model\Player;
use App\Repository\RoomRepository;
use App\Repository\UserRepository;
use App\Service\Card\CardGenerator;
use App\Service\Card\HandRepository;
use App\Service\GameContextProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class HomeController extends AbstractController
{
    public function __construct(
        private readonly CardGenerator $cardGenerator,
        private readonly HubInterface $hub,
        private readonly RoomRepository $roomRepository,
        private readonly UserRepository $userRepository,
        private readonly SerializerInterface $serializer,

        private readonly HandRepository $handRepository,
    ) {
    }

    #[Route('/', name: 'home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig');
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
                    'player' =>  new Player($user->getUsername()),   
                ])
            ));
        }

        $players =  array_map(
            fn ($player) => new Player($player->getUsername()),
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
        $hands = $this->cardGenerator->generateHands(2, 5);
        $response = $this->redirectToRoute('game', ['id' => $room->getId()]);

        foreach ($room->getPlayers() as $k => $player) {
            $this->handRepository->save($player, $room, $hands[$k]);
        }
        
        $this->hub->publish(new Update(
            sprintf('game-%s', $room->getId()),
            json_encode([
                'url' => $response->getTargetUrl(),
            ])
        ));

        return $response;
    }

    #[Route('/game/{id}', name: 'game')]
    public function game(Room $room, GameContextProvider $gameContextProvider): Response
    {
        $user = $this->getUser();

        return $this->render('home/game.html.twig', [
            'game' => $this->serializer->serialize($gameContextProvider->provide($room), 'json'),
            'player' => $this->serializer->serialize($this->getUser(), 'json'),
            'hand' => $this->handRepository->get($user, $room)->getCards(),
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
