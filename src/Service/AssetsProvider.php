<?php

declare(strict_types=1);

namespace App\Service;

use App\Enum\DeckSkinEnum;
use App\Game\Model\Card\Card;
use Symfony\Component\Asset\Packages;

final class AssetsProvider
{
    public function __construct(
        private readonly Packages $packages,
        private readonly string $assetsPath,
    ) {
    }

    /**
     * @param array<Card> $cards
     *
     * @return array<string, string>
     */
    public function getAssets(array $cards, DeckSkinEnum $skin = DeckSkinEnum::DEFAULT): array
    {
        $assets = array_reduce(
            $cards,
            function (array $carry, Card $card) use ($skin): array {
                $carry[(string) $card] = $this->getAssertFromString($card->getImg(), $skin);

                return $carry;
            },
            [],
        );
        $assets['back'] = $this->getAssertFromString('back.svg', $skin);

        return $assets;
    }

    private function getAssertFromString(string $card, DeckSkinEnum $skin): string
    {
        if (file_exists(\sprintf('%s/%s', $this->assetsPath, $fileName = $this->getAssetPath($card, $skin)))) {
            return $this->packages->getUrl($fileName);
        }

        return $this->packages->getUrl($this->getAssetPath($card, DeckSkinEnum::DEFAULT));
    }

    private function getAssetPath(string $card, DeckSkinEnum $skin): string
    {
        return \sprintf('resources/%s/%s', $skin->value, $card);
    }
}
