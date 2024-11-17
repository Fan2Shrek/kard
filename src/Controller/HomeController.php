<?php

namespace App\Controller;

use App\Entity\Room;
use App\Entity\User;
use App\Enum\Card\Rank;
use App\Enum\Card\Suit;
use App\Model\Card\Card;
use App\Model\Card\Hand;
use App\Model\Player;
use App\Repository\RoomRepository;
use App\Repository\UserRepository;
use App\Service\Card\CardGenerator;
use App\Service\GameContextProvider;
use App\Service\GamePlayer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class HomeController extends AbstractController
{
    public function __construct(
        private readonly CardGenerator $cardGenerator,
        private readonly HubInterface $hub,
        private readonly RoomRepository $roomRepository,
        private readonly UserRepository $userRepository,
        private readonly CacheInterface $cache,
        private readonly SerializerInterface $serializer,
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
            $this->cache->get(sprintf('player-%s', $player->getUsername()), function (ItemInterface $item) use ($hands, $k) {
                $item->expiresAfter(60*60*24);

                return $hands[$k];
            });
        }
        
        $this->hub->publish(new Update(
            sprintf('game-%s', $room->getId()),
            json_encode([
                'url' => $response->getTargetUrl(),
            ])
        ));

        return $response;
    }

    /**
     * @change for #[MapRequestPayload] Card $card
     * @see https://github.com/symfony/symfony/issues/58840
     */
    #[Route('/api/{id}/play', name: 'play', methods: ['POST'])]
    public function play(Room $room, Request $request): Response
    {
        $data = $request->toArray()['card'];
        $card = new Card(Suit::from($data['suit']), Rank::from($data['rank']));

        // @todo: check if the card is in the player's hand
        $this->hub->publish(new Update(
            sprintf('/room/%s/%s', $room->getId(), $this->getUser()->getId()),
            $this->renderView('components/turbo/card-played.html.twig', [
                'card' => $card,
                'player' => $this->getUser(),
            ])
        ));

        $this->hub->publish(new Update(
            sprintf('/room/%s', $room->getId()),
            $this->renderView('components/turbo/hidden-card-played.html.twig', [
                'card' => $card,
                'player' => $this->getUser(),
            ])
        ));

        return new JsonResponse();
    }

    #[Route('/game/{id}', name: 'game')]
    public function game(Room $room, GameContextProvider $gameContextProvider): Response
    {
        $user = $this->getUser();
        /** @var Hand $hand */
        $hand = $this->cache->get(sprintf('player-%s', $user->getUsername()), function (ItemInterface $item) {
            $item->expiresAfter(3600);

            return json_encode([
                'hand' => [],
            ]);
        });

        return $this->render('home/game.html.twig', [
            'game' => $this->serializer->serialize($gameContextProvider->provide($room), 'json'),
            'player' => $this->serializer->serialize($this->getUser(), 'json'),
            'hand' => $hand->getCards(),
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
