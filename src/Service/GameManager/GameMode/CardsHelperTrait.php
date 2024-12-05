<?php

namespace App\Service\GameManager\GameMode;

use App\Model\Card\Card;

trait CardsHelperTrait
{
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
}
