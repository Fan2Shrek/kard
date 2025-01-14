<?php

namespace App\Service\GameManager\GameMode;

use App\Enum\Card\Rank;
use App\Model\Card\Card;

trait CardsHelperTrait
{
    private function isLegacyHigher(Card $card, Card $currentCard): bool
    {
        $ranks = $this->getRanks();
        if (!in_array($card->rank, $ranks, true) || !in_array($currentCard->rank, $ranks, true)) {
            throw new \Exception(\sprintf('The card rank "%s" or the current card rank "%s" is not in the RANKS constant', __METHOD__, !in_array($card->rank, $ranks, true) ? $card->rank->value : $currentCard->rank->value));
        }

        return array_search($card->rank, $ranks) > array_search($currentCard->rank, $ranks);
    }

    private function isHigher(Card $card, Card $currentCard): bool
    {
        return $card->rank->value > $currentCard->rank->value;
    }

    private function isLower(Card $card, Card $currentCard): bool
    {
        return $card->rank->value < $currentCard->rank->value;
    }

    private function isSameRank(Card $card, Card $currentCard): bool
    {
        return $card->rank->value === $currentCard->rank->value;
    }

    /**
     * @param Card[] $cards
     */
    private function allSameRank(array $cards): bool
    {
        $rank = $cards[0]->rank->value;
        foreach ($cards as $card) {
            if ($card->rank->value !== $rank) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<int, Rank>
     */
    protected function getRanks(): array
    {
        return Rank::cases();
    }
}
