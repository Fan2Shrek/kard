<?php

namespace App\Service;

use App\Entity\Room;
use App\Model\Card\Card;
use App\Model\GameContext;
use App\Model\Player;
use App\Service\Card\CardGenerator;
use App\Service\Card\HandRepository;
use App\Service\Redis\RedisConnection;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

final class GameContextProvider
{
    public function __construct(
        private readonly HandRepository $handRepository,
        private readonly Packages $packages,
        private readonly CardGenerator $cardGenerator,
        private readonly RedisConnection $redis,
        private readonly SerializerInterface $serializer,
    ) {
    }

    public function provide(Room $room): GameContext
    {
        if ('' === $ctx = $this->redis->get($this->getKey($room))) {
            $ctx = $this->createContext($room);
        }

        return is_string($ctx) ? $this->serializer->deserialize($ctx, GameContext::class, 'json', [
            AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT => true,
        ]) : $ctx;
    }

    public function save(GameContext $ctx): void
    {
        $this->redis->set($this->getKey($ctx->getRoom()), $this->serializer->serialize($ctx, 'json'));
    }

    private function createContext(Room $room): GameContext
    {
        $cards = $this->format($this->getDeck($room));
        $cards['back'] = $this->packages->getUrl('resources/back.svg');

        $players = array_map(fn ($u) => Player::fromUser($u), $room->getPlayers()->toArray());

        return new GameContext(
            $room->getId(),
            $room,
            $cards,
            $players,
            $players[0],
        );
    }

    private function getDeck(Room $room): array
    {
        // @todo:
        return $this->cardGenerator->generate()->getCards();
        $deck = [];
        foreach ($room->getPlayers() as $player) {
            $deck = array_merge($this->handRepository->get($player, $room)->getCards(), $deck);
        }

        return $deck;
    }

    private function format(array $deck): array
    {
        return array_reduce(
            $deck,
            function (array $carry, Card $card) {
                $carry[(string) $card] = $this->packages->getUrl('resources/'.$card->getImg());

                return $carry;
            },
            [],
        );
    }

    private function getKey(Room $room): string
    {
        return sha1('game-context--'.$room->getId());
    }
}
