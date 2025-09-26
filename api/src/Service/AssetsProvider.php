<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Card\Card;
use App\Service\Card\CardGenerator;
use Symfony\Component\Asset\Packages;

final class AssetsProvider
{
    public function __construct(
        private readonly Packages $packages,
        private readonly CardGenerator $cardGenerator,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function getAllCardsAssets(): array
    {
        $cards = array_reduce(
            $this->cardGenerator->generate()->getCards(),
            function (array $carry, Card $card) {
                $carry[(string) $card] = $this->packages->getUrl('resources/'.$card->getImg());

                return $carry;
            },
            [],
        );
        $cards['back'] = $this->packages->getUrl('resources/back.svg');

        return $cards;
    }
}
