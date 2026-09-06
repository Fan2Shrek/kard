<?php

declare(strict_types=1);

namespace App\Game\Builder;

use App\Enum\Card\Rank;
use App\Enum\Card\Suit;
use App\Game\Model\Card\Card;
use App\Game\Model\Card\Deck;
use Ramsey\Uuid\Uuid;

final class DeckBuilder
{
    /**
     * @var Card[]
     */
    private array $cards = [];

    private int $deckCount = 1;

    private bool $withJokers = false;

    public function build(): Deck
    {
        for ($i = 0; $i < $this->deckCount; ++$i) {
            $this->cards = array_merge($this->cards, $this->getOneDeckCards());
        }

        return new Deck($this->cards);
    }

    public function withJokers(): self
    {
        $this->withJokers = true;

        return $this;
    }

    public function withDeckCount(int $count): self
    {
        $this->deckCount = $count;

        return $this;
    }

    /**
     * @return Card[]
     */
    private function getOneDeckCards(): array
    {
        $cards = [];

        foreach (Rank::valueCases() as $rank) {
            foreach (Suit::cases() as $suit) {
                $cards[] = new Card(Uuid::uuid4()->toString(), $rank, $suit);
            }
        }

        if ($this->withJokers) {
            $cards[] = new Card(Uuid::uuid4()->toString(), Rank::JOKER, null);
            $cards[] = new Card(Uuid::uuid4()->toString(), Rank::JOKER, null);
        }

        return $cards;
    }
}
