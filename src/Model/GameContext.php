<?php

namespace App\Model;

use App\Entity\Room;
use App\Model\Card\Card;

final class GameContext
{
    private PlayersList $players;
    private GameRound $currentRound;

    /**
     * @param Card[]   $assets
     * @param Player[] $players
     * @param Card[]   $turns
     * @param Card[]   $discarded
     */
    public function __construct(
        private string $id,
        private Room $room,
        private array $assets,
        array $players,
        Player $currentPlayer,
        array $turns = [],
        private array $discarded = [],
        private array $data = [],
    ) {
        $this->players = new PlayersList($players, $currentPlayer);
        $this->currentRound = new GameRound($turns);
    }

    public function newRound(): void
    {
        foreach ($this->currentRound->getTurns() as $turn) {
            $this->discarded = array_merge($this->discarded, $turn->getCards());
        }

        $this->currentRound = new GameRound();
    }

    public function setPlayerOrder(array $players): void
    {
        $this->players = new PlayersList($players, $this->players->getCurrentPlayer());
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getData(?string $key = null): mixed
    {
        if ($key) {
            return $this->data[$key] ?? null;
        }

        return $this->data;
    }

    public function addData(string $key, $value): void
    {
        $this->data[$key] = $value;
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
        return $this->currentRound->getCurrentTurn()?->getCards() ?? [];
    }

    public function addCurrentCard(Card $card): void
    {
        $this->currentRound->getCurrentTurn()->addCard($card);
    }

    public function getRound(): GameRound
    {
        return $this->currentRound;
    }

    public function setRound(GameRound $round): self
    {
        $this->currentRound = $round;

        return $this;
    }

    public function getDiscarded(): array
    {
        return $this->discarded;
    }

    public function addToDeck(Card $card): void
    {
        $this->assets[] = $card;
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
        $this->currentRound->addTurn(new Turn($cards));
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
class PlayersList
{
    private int $currentIndex;

    public function __construct(
        private array $players,
        private Player $currentPlayer,
    ) {
        $this->currentIndex = array_search($currentPlayer, $players);
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
            ++$this->currentIndex;
        }

        $this->currentPlayer = $this->players[$this->currentIndex];
    }

    public function toArray(): array
    {
        return $this->players;
    }
}
