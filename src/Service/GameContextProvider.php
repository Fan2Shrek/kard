<?php

namespace App\Service;

use App\Entity\Room;
use App\Model\Card\Card;
use App\Model\GameContext;
use App\Service\Card\CardGenerator;
use App\Service\Card\HandRepository;
use Symfony\Component\Asset\Packages;

final class GameContextProvider
{
    public function __construct(
        private readonly HandRepository $handRepository,
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
}
