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

    public function generateHands(int $handsCount, int $cards = 0): array
    {
        $deck = $this->generateShuffled();

        if ($cards === 0) {
            $cards = $deck->count() / $handsCount;
        }

        return [
            new Hand([
                new Card(Suit::HEARTS, RANK::SEVEN),
                new Card(Suit::SPADES, RANK::SEVEN),
                new Card(Suit::HEARTS, RANK::THREE),
                new Card(Suit::SPADES, RANK::THREE),
            ]),
            new Hand([
                new Card(Suit::DIAMONDS, RANK::SEVEN),
                new Card(Suit::CLUBS, RANK::SEVEN),
                new Card(Suit::DIAMONDS, RANK::THREE),
                new Card(Suit::CLUBS, RANK::THREE),
            ]),
        ];

        $hands = [];

        for ($i = 0; $i < $handsCount; $i++) {
            $hand = new Hand();

            for ($j = 0; $j < $cards; $j++) {
                $hand->add($deck->draw());
            }

            $hands[] = $hand;
        }

        return $hands;
    }
}
