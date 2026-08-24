<?php

namespace App\Game\Card;

use App\Enum\Card\Rank;
use App\Enum\Card\Suit;
use App\Game\Model\Card\Card;
use App\Game\Model\Card\Deck;
use App\Game\Model\Card\Hand;

final class CardGenerator
{
    public function generate(bool $withJokers = false): Deck
    {
        $cards = [];

        foreach (Suit::cases() as $suit) {
            foreach (Rank::valueCases() as $rank) {
                $cards[] = new Card($rank, $suit);
            }
        }

        if ($withJokers) {
            $cards[] = new Card(Rank::JOKER, null);
            $cards[] = new Card(Rank::JOKER, null);
        }

        return new Deck($cards);
    }

    public function generateShuffled(): Deck
    {
        return $this->generate()->withShuffled();
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
                [$deck, $card] = $deck->withDrawnCard();
                $hand->add($card);
            }

            $hands[] = $hand;
        }

        return [$hands, $deck->getCards()];
    }
}
