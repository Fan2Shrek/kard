<?php

namespace App\Service\Card;

use App\Enum\Card\Rank;
use App\Enum\Card\Suit;
use App\Model\Card\Card;
use App\Model\Card\Deck;
use App\Model\Card\Hand;

final class CardGenerator
{
    public function generate(): Deck
    {
        $cards = [];

        foreach (Suit::cases() as $suit) {
            foreach (Rank::cases() as $rank) {
                $cards[] = new Card($suit, $rank);
            }
        }

        return new Deck($cards);
    }

    public function generateShuffled(): Deck
    {
        $deck = $this->generate();
        $deck->shuffle();

        return $deck;
    }

    /**
     * @return array{
     *    0: Hand[],
     *    1: Card[],
     * }
     */
    public function generateHands(int $handsCount, int $cards = 0): array
    {
        $deck = $this->generateShuffled();

        if (0 === $cards) {
            $baseCards = intdiv($deck->count(), $handsCount);
            $remainder = $deck->count() % $handsCount;

            $cardsPerHand = array_fill(0, $handsCount, $baseCards);

            for ($i = 0; $i < $remainder; ++$i) {
                ++$cardsPerHand[$i];
            }
        } else {
            $cardsPerHand = array_fill(0, $handsCount, $cards);
        }

        $hands = [];

        foreach ($cardsPerHand as $cards) {
            $hand = new Hand();

            foreach (range(0, $cards - 1) as $j) {
                $hand->add($deck->draw());
            }

            $hands[] = $hand;
        }

        return [$hands, $deck->getCards()];
    }
}
