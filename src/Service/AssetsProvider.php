<?php

declare(strict_types=1);

namespace App\Service;

use App\Game\Model\Card\Card;
use Symfony\Component\Asset\Packages;

final class AssetsProvider
{
    public function __construct(
        private readonly Packages $packages,
    ) {
    }

    /**
	 * @param array<Card> $cards
	 *
     * @return array<string, string>
     */
    public function getAssets(array $cards): array
    {
        $assets = array_reduce(
            $cards,
            function (array $carry, Card $card) {
                $carry[(string) $card] = $this->packages->getUrl('resources/'.$card->getImg());

                return $carry;
            },
			[],
        );
        $assets['back'] = $this->packages->getUrl('resources/back.svg');

        return $assets;
    }
}
