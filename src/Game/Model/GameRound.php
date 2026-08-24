<?php

namespace App\Game\Model;

final readonly class GameRound
{
    /**
     * @param Turn[] $turns
     */
    public function __construct(
        private array $turns = [],
    ) {
    }

    public function getTurn(int $index): ?Turn
    {
        return $this->turns[$index] ?? null;
    }

    public function getCurrentTurn(): ?Turn
    {
        $turns = $this->turns;

        return end($turns) ?: null;
    }

    /**
     * @return Turn[]
     */
    public function getTurns(): array
    {
        return $this->turns;
    }
}
