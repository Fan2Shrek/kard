<?php

namespace App\Model;


final class GameRound
{
    /**
     * @var Turn[]
     */
    private array $turns;

    public function __construct(
        array $turns = [],
    ) {
        $this->turns = $turns;
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

    public function getTurns(): array
    {
        return $this->turns;
    }
}
