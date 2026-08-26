<?php

declare(strict_types=1);

namespace App\Game\Model\State;

use App\Game\Model\Card\Hand;

final readonly class PlayerState
{
    public function __construct(
        public string $id,
        public string $playerName,
        public int $score,
        public Hand $hand,
        public bool $isBot = false,
    ) {
    }

    public function withScore(int $score): self
    {
        return new self($this->id, $this->playerName, $score, $this->hand, $this->isBot);
    }

    public function withHand(Hand $hand): self
    {
        return new self($this->id, $this->playerName, $this->score, $hand, $this->isBot);
    }

    public function addCard(string $cardId): self
    {
        return new self(
            $this->id,
            $this->playerName,
            $this->score,
            $this->hand->addCard($cardId),
            $this->isBot,
        );
    }

    public function discardCard(string $cardId): self
    {
        return new self(
            $this->id,
            $this->playerName,
            $this->score,
            $this->hand->removeCard($cardId),
            $this->isBot,
        );
    }
}
