<?php

namespace App\Game\Model;

use App\Entity\Room;
use App\Game\Model\Card\Card;
use App\Game\Model\Card\Deck;

final readonly class GameState
{
    private PlayersList $players;

    private GameRound $currentRound;

    private ?Player $winner;

    private Deck $drawPill;

    /**
     * @param Player[] $players
     * @param Turn[]   $turns
     * @param Card[]   $drawPill
     * @param Card[]   $discarded
     * @param mixed[]  $data
     */
    public function __construct(
        private string $id,
        private Room $room,
        array $players,
        Player $currentPlayer,
        array $turns = [],
        array $drawPill = [],
        private array $discarded = [],
        private array $data = [],
        ?Player $winner = null,
    ) {
        $this->players = new PlayersList($players, $currentPlayer);
        $this->currentRound = new GameRound($turns);
        $this->drawPill = new Deck($drawPill);
        $this->winner = $winner;
    }

    public function withNewRound(): self
    {
        $discarded = $this->discarded;
        foreach ($this->currentRound->getTurns() as $turn) {
            $discarded = [...$discarded, ...$turn->getCards()];
        }

        return $this->cloneWith(turns: [], discarded: $discarded);
    }

    /**
     * @param Player[] $players
     */
    public function withPlayerOrder(array $players, bool $keepCurrentPlayer = false): self
    {
        return $this->cloneWith(
            players: $players,
            currentPlayer: $keepCurrentPlayer ? $this->players->getCurrentPlayer() : $players[0],
        );
    }

    public function withNextPlayer(): self
    {
        return $this->cloneWith(currentPlayer: $this->players->getNextPlayer());
    }

    public function getNextPlayer(): Player
    {
        return $this->players->getNextPlayer();
    }

    /**
     * @param Card[] $drawPile
     */
    public function withDrawPile(array $drawPile): self
    {
        return $this->cloneWith(drawPill: $drawPile);
    }

    /**
     * @return array{0: self, 1: Card[]} the new state and the drawn cards
     */
    public function withDrawnCards(int $count): array
    {
        if ([] === $this->drawPill->getCards()) {
            return [$this, []];
        }

        $deck = $this->drawPill;
        $cards = [];

        for ($i = 0; $i < $count; ++$i) {
            [$deck, $card] = $deck->withDrawnCard();
            $cards[] = $card;
        }

        return [$this->cloneWith(drawPill: $deck->getCards()), $cards];
    }

    /**
     * @return Card[] an array of cards in the draw pile
     */
    public function getDrawPile(): array
    {
        return $this->drawPill->getCards();
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

    public function withData(string $key, mixed $value): self
    {
        $data = $this->data;
        $data[$key] = $value;

        return $this->cloneWith(data: $data);
    }

    public function isFastPlay(): bool
    {
        return (bool) ($this->data['fastPlay'] ?? false);
    }

    public function withFastPlay(bool $fastPlay): self
    {
        return $this->withData('fastPlay', $fastPlay);
    }

    public function getLastPlayerId(): ?string
    {
        return $this->data['lastPlayer'] ?? null;
    }

    public function withLastPlayer(string $playerId): self
    {
        return $this->withData('lastPlayer', $playerId);
    }

    public function getRoom(): Room
    {
        return $this->room;
    }

    /**
     * @return Player[]
     */
    public function getPlayers(): array
    {
        return $this->players->toArray();
    }

    public function getCurrentPlayer(): Player
    {
        return $this->players->getCurrentPlayer();
    }

    /**
     * @return Card[]
     */
    public function getCurrentCards(): array
    {
        return $this->currentRound->getCurrentTurn()?->getCards() ?? [];
    }

    public function getRound(): GameRound
    {
        return $this->currentRound;
    }

    /**
     * @return Card[]
     */
    public function getDiscarded(): array
    {
        return $this->discarded;
    }

    /**
     * @param Card[] $cards
     */
    public function withCurrentCards(array $cards): self
    {
        return $this->cloneWith(turns: [...$this->currentRound->getTurns(), new Turn($cards)]);
    }

    /**
     * @param Card[] $cards
     */
    public function withDiscarded(array $cards): self
    {
        return $this->cloneWith(discarded: $cards);
    }

    public function getWinner(): ?Player
    {
        return $this->winner;
    }

    public function withWinner(Player $winner): self
    {
        return $this->cloneWith(winner: $winner);
    }

    public function withAddedPlayer(Player $player): self
    {
        return $this->cloneWith(players: [...$this->players->toArray(), $player]);
    }

    public function withUpdatedPlayer(Player $player): self
    {
        $players = array_map(
            fn (Player $p): Player => $p->id === $player->id ? $player : $p,
            $this->players->toArray(),
        );

        return $this->cloneWith(players: $players);
    }

    public function withCurrentPlayer(Player $player): self
    {
        return $this->cloneWith(currentPlayer: $player);
    }

    /**
     * @param Player[]|null $players
     * @param Turn[]|null   $turns
     * @param Card[]|null   $drawPill
     * @param Card[]|null   $discarded
     * @param mixed[]|null  $data
     */
    private function cloneWith(
        ?array $players = null,
        ?Player $currentPlayer = null,
        ?array $turns = null,
        ?array $drawPill = null,
        ?array $discarded = null,
        ?array $data = null,
        ?Player $winner = null,
    ): self {
        return new self(
            $this->id,
            $this->room,
            $players ?? $this->players->toArray(),
            $currentPlayer ?? $this->players->getCurrentPlayer(),
            $turns ?? $this->currentRound->getTurns(),
            $drawPill ?? $this->drawPill->getCards(),
            $discarded ?? $this->discarded,
            $data ?? $this->data,
            $winner ?? $this->winner,
        );
    }
}

/**
 * @internal
 */
final readonly class PlayersList
{
    private int $currentIndex;

    /**
     * @param Player[] $players
     */
    public function __construct(
        private array $players,
        Player $currentPlayer,
    ) {
        $this->currentIndex = $this->findIndex($currentPlayer->id);
    }

    public function getCurrentPlayer(): Player
    {
        return $this->players[$this->currentIndex];
    }

    public function getNextPlayer(): Player
    {
        if ($this->currentIndex === count($this->players) - 1) {
            return $this->players[0];
        }

        return $this->players[$this->currentIndex + 1];
    }

    /**
     * @return Player[]
     */
    public function toArray(): array
    {
        return $this->players;
    }

    private function findIndex(string $playerId): int
    {
        foreach ($this->players as $index => $player) {
            if ($player->id === $playerId) {
                return $index;
            }
        }

        throw new \InvalidArgumentException(\sprintf('Player "%s" is not part of this players list', $playerId));
    }
}
