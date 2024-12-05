<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Entity\Room;
use App\Enum\Card\Rank;
use App\Enum\Card\Suit;
use App\Model\Card\Card;
use App\Service\GameManager\GameManager;
use App\Service\Redis\RedisConnection;
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
        private readonly HubInterface $hub,
        private readonly GameManager $gameManager,
    ) {
    }

    /**
     * @change for #[MapRequestPayload] Card $card
     * @see https://github.com/symfony/symfony/issues/58840
     */
    #[Route('/{id}/play', name: 'play', methods: ['POST'])]
    public function play(RedisConnection $redis, Room $room, Request $request): Response
    {
        $data = $request->toArray()['cards'];
        $cards = array_map(fn ($card) => new Card(Suit::from($card['suit']), Rank::from($card['rank'])), $data);

        $this->gameManager->play($room, $this->getUser(), $cards);
        
        return new JsonResponse();

        $redis->set(sprintf('%s-%s', $room->getId(), $this->getUser()->getUsername()), json_encode($card));

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

        $cards = $players = [];
        foreach ($room->getPlayers() as $player) {
            if (!$card = $redis->get(sprintf('%s-%s', $room->getId(), $player->getUsername()))) {
                return new JsonResponse();
            }

            $cards[$player->getId()->toString()] = json_decode($card, true);
            $players[$player->getId()->toString()] = $player;
        }

        $bestCard = $winner = null;
        foreach ($cards as $playerId => $card) {
            if (null === $bestCard) {
                $bestCard = $card;
                $winner = $playerId;
                continue;
            }

            if ($card['rank'] > $bestCard['rank']) {
                $bestCard = $card;
                $winner = $playerId;
            }
        }

        $this->hub->publish(new Update(
            sprintf('/room/%s', $room->getId()),
            $this->renderView('components/turbo/winner.html.twig', [
                'player' => $players[$winner],
            ])
        ));

        return new JsonResponse();
    }

    private function todo(): void
    {
        /*$this->gameManager->play($this->getUser()->getRoom());*/
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
