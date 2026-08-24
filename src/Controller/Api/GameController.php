<?php

namespace App\Controller\Api;

use App\Entity\Room;
use App\Entity\User;
use App\Enum\Card\Rank;
use App\Enum\Card\Suit;
use App\Game\Card\HandRepositoryInterface;
use App\Game\GameManager;
use App\Game\GameStateProvider;
use App\Game\Model\Card\Card;
use App\Game\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/game')]
final class GameController extends AbstractController
{
    public function __construct(
        private readonly GameManager $gameManager,
        private readonly GameStateProvider $gameStateProvider,
        private readonly HandRepositoryInterface $handRepository,
        private readonly SerializerInterface $serializer,
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
        $user = $this->getUser();
        $state = $this->gameStateProvider->provide($room);

        $hand = null;
        if (\in_array($user, $room->getParticipants()->toArray(), true)) {
            $hand = $this->handRepository->get($user, $room)?->getCards();
        }

        return new JsonResponse($this->serializer->serialize([
            'state' => $state,
            'hand' => $hand,
        ], 'json'), Response::HTTP_OK, [], true);
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
        $card = $request->toArray()['cards'];
        $data = $request->toArray()['data'];

        $cards = array_map(fn ($card): Card => new Card(Rank::from($card['rank']), Suit::from($card['suit'])), $card);
        $player = current(array_filter(
            $room->getPlayers(),
            fn (Player $p): bool => $p->id === $user->getId()->toString(),
        ));

        if (false === $player) {
            return new JsonResponse(['error' => 'Player not found'], Response::HTTP_NOT_FOUND);
        }

        $this->gameManager->play($room, $player, $cards, $data);

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
