<?php

namespace App\Service;

use App\Entity\Room;
use App\Model\Card\Card;
use App\Model\GameContext;
use App\Service\Card\CardGenerator;
use Symfony\Component\Asset\Packages;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class GameContextProvider
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly Packages $packages,
        private readonly CardGenerator $cardGenerator,
    ) {
    }

    public function provide(Room $room): GameContext
    {
        $cards = $this->format($this->getDeck($room));
        $cards['back'] = $this->packages->getUrl('resources/back.svg');

        return new GameContext(
            $room,
            $cards,
        );
    }

    private function getDeck(Room $room): array
    {
        $deck = [];
        foreach ($room->getPlayers() as $player) {
            $deck = array_merge($deck, $this->cache->get(
                sprintf('player-%s', $player->getUsername()),
                fn (ItemInterface $item) => $item->get(),
            )->getCards());
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
}
