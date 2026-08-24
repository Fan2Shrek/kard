<?php

namespace App\Game\Mode;

use App\Enum\Card\Rank;
use App\Enum\Card\Suit;
use App\Game\Model\Card\Card;
use App\Game\Model\State\Round;
use App\Game\Model\State\Turn;

trait CardsHelperTrait
{
    private function isHigherByRankOrder(Card $card, Card $currentCard): bool
    {
        $ranks = $this->getRanks();
        if (!in_array($card->rank, $ranks, true) || !in_array($currentCard->rank, $ranks, true)) {
            throw new \Exception(\sprintf('The card rank "%s" or the current card rank "%s" is not in the RANKS constant', __METHOD__, !in_array($card->rank, $ranks, true) ? $card->rank->value : $currentCard->rank->value));
        }

        return array_search($card->rank, $ranks, true) > array_search($currentCard->rank, $ranks, true);
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

    private function isSameSuit(Card $card, Card $currentCard): bool
    {
        return $card->suit->value === $currentCard->suit->value;
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
     * @param Card[] $cards
     */
    private function allSameSuit(array $cards): bool
    {
        $suit = $cards[0]->suit->value;
        foreach ($cards as $card) {
            if ($card->suit->value !== $suit) {
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

	protected function getLastNonSkippedTurn(Round $round): Turn
	{
		foreach (array_reverse($round->turns) as $turn) {
			if (!$turn->hasBeenSkipped()) {
				return $turn;
			}
		}

		throw new \RuntimeException('No non-skipped turn found in round.');
	}

	/**
	 * @return Turn[] the last $count non-skipped turns, in chronological order - skips are ignored, not treated as breaking the sequence
	 */
	protected function getLastNonSkippedTurns(Round $round, int $count): array
	{
		$nonSkipped = array_values(array_filter($round->turns, fn (Turn $turn): bool => !$turn->hasBeenSkipped()));

		return array_slice($nonSkipped, -$count);
	}
}
