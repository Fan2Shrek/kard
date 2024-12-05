<?php

namespace App\Model;

use App\Entity\Room;
use App\Model\Card\Card;


final class GameContext
{
    /**
     * @param Card[] $assets
     * @param Card[] $currentCards
     * @param Card[] $discarded
     */
    public function __construct(
        private Room $room,
        private array $assets,
        private array $currentCards = [],
        private array $discarded = [],
    ) {}

    public function getRoom(): Room
    {
        return $this->room;
    }

    public function getAssets(): array
    {
        return $this->assets;
    }

    public function getCurrentCards(): array
    {
        return $this->currentCards;
    }

    public function getDiscarded(): array
    {
        return $this->discarded;
    }

    public function addToDeck(Card $card): void
    {
        $this->assets[] = $card;
    }

    public function addToCurrentCard(Card $card): void
    {
        $this->currentCards[] = $card;
    }

    public function addToDiscarded(Card $card): void
    {
        $this->discarded[] = $card;
    }

    public function setCurrentCards(array $cards): void
    {
        foreach ($this->currentCards as $card) {
            $this->addToDiscarded($card);
        }

        $this->currentCards = $cards;
    }

    public function setDiscarded(array $cards): void
    {
        $this->discarded = $cards;
    }
}
