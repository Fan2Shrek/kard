<?php

namespace App\Controller\Api;

use App\Domain\DTO\GameStateDTO;
use App\Entity\Room;
use App\Entity\User;
use App\Game\GameManager;
use App\Game\StateProvider\GameStateProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/game')]
final class GameController extends AbstractController
{
    public function __construct(
        private readonly GameManager $gameManager,
        private readonly GameStateProviderInterface $gameStateProvider,
    ) {
    }

    /**
     * Returns the public game state, plus the connected player's hand when they are a participant.
     *
     * Used by the front to resync after receiving a game event over Mercure.
     */
    #[Route('/{id}', name: 'state', methods: ['GET'])]
    public function state(Room $room): Response
    {
        $state = $this->gameStateProvider->get($room->getId()->toString());
        $user = parent::getUser();
        $viewerId = $user instanceof User ? $user->getId()->toString() : null;

        return $this->json(GameStateDTO::fromState($state, $viewerId));
    }

    /**
     * @change for #[MapRequestPayload] Card $card
     *
     * @see https://github.com/symfony/symfony/issues/58840
     */
    #[Route('/{id}/play', name: 'play', methods: ['POST'])]
    public function play(Room $room, Request $request): Response
    {
        $request->attributes->set('_format', 'json');
        $user = $this->getUser();
        $cards = $request->toArray()['cards'];
        $data = $request->toArray()['data'];

        $this->gameManager->play($room, $user, $cards, $data);

        return new JsonResponse();
    }

    #[Route('/{id}/add_ai', name: 'add_ai', methods: ['POST'])]
    public function addAi(
        Room $room,
        HubInterface $hub,
        RoomRepository $roomRepository,
    ): Response {
        $player = GameAI::create();
        $room->addBot($player->id, $player);
        $roomRepository->save($room);

        $hub->publish(new Update(
            'waiting',
            $this->renderView('components/turbo/player-join.html.twig', [
                'player' => $player,
            ])
        ));

        return new JsonResponse();
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
