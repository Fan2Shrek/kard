<?php

namespace App\Controller\Api;

use App\Entity\Room;
use App\Entity\User;
use App\Enum\Card\Rank;
use App\Enum\Card\Suit;
use App\Model\Card\Card;
use App\Service\GameManager\GameManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/game')]
final class GameController extends AbstractController
{
    public function __construct(
        private readonly GameManager $gameManager,
    ) {
    }

    /**
     * @change for #[MapRequestPayload] Card $card
     *
     * @see https://github.com/symfony/symfony/issues/58840
     */
    #[Route('/{id}/play', name: 'play', methods: ['POST'])]
    public function play(Room $room, Request $request): Response
    {
        $user = $this->getUser();
        $data = $request->toArray()['cards'];
        $cards = array_map(fn ($card) => new Card(Suit::from($card['suit']), Rank::from($card['rank'])), $data);

        $this->gameManager->play($room, $user, $cards);

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
