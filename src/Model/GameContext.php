<?php

namespace App\Model;

use App\Entity\Room;
use App\Model\Card\Card;


final class GameContext
{
    private PlayersList $players;

    /**
     * @param Card[] $assets
     * @param Card[] $currentCards
     * @param Card[] $discarded
     */
    public function __construct(
        private string $id,
        private Room $room,
        private array $assets,
        array $players,
        Player $currentPlayer,
        private array $currentCards = [],
        private array $discarded = [],
    ) {
        $this->players = new PlayersList($players, $currentPlayer);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getRoom(): Room
    {
        return $this->room;
    }

    public function getAssets(): array
    {
        return $this->assets;
    }

    public function getPlayers(): array
    {
        return $this->players->toArray();
    }

    public function getCurrentPlayer(): Player
    {
        return $this->players->getCurrentPlayer();
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

    public function addCurrentCard(Card $card): void
    {
        $this->currentCards[] = $card;
    }

    public function addDiscarded(Card $card): void
    {
        $this->discarded[] = $card;
    }

    /**
     * @param Card[] $cards
     */
    public function setCurrentCards(array $cards): void
    {
        foreach ($this->currentCards as $card) {
            $this->addDiscarded($card);
        }

        $this->currentCards = $cards;
    }

    public function setDiscarded(array $cards): void
    {
        $this->discarded = $cards;
    }

    public function addPlayer(Player $player): void
    {
        $this->players[] = $player;
    }

    public function nextPlayer(): void
    {
        $this->players->nextPlayer();
    }
}

/**
 * @internal
 */
class PlayersList {
    private int $currentIndex = 0;

    public function __construct(
        private array $players,
        private Player $currentPlayer,
    ) {
    }

    public function getCurrentPlayer(): Player
    {
        return $this->currentPlayer;
    }

    public function nextPlayer(): void
    {
        if ($this->currentIndex === count($this->players) - 1) {
            $this->currentIndex = 0;
        } else {
            $this->currentIndex++;
        }

        $this->currentPlayer = $this->players[$this->currentIndex];
    }

    public function toArray(): array
    {
        return $this->players;
    }
}
