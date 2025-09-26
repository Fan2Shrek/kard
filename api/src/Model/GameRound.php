<?php

namespace App\Model;

final class GameRound
{
    /**
     * @param Turn[] $turns
     */
    public function __construct(
        private array $turns = [],
    ) {
    }

    public function addTurn(Turn $turn): void
    {
        $this->turns[] = $turn;
    }

    public function getTurn(int $index): ?Turn
    {
        return $this->turns[$index] ?? null;
    }

    public function getCurrentTurn(): ?Turn
    {
        return end($this->turns) ?: null;
    }

    /**
     * @return Turn[]
     */
    public function getTurns(): array
    {
        return $this->turns;
    }

    /**
     * @param Turn[] $turns
     */
    public function setTurns(array $turns): self
    {
        $this->turns = $turns;

        return $this;
    }
}
