<?php

namespace App\Model;


final class GameRound
{
    /**
     * @var Turn[]
     */
    private array $turns;

    public function __construct(
        array $currentCards,
    ) {
        $this->turns = [new Turn($currentCards)];
    }

    public function addTurn(array $cards): void
    {
        $this->turns[] = new Turn($cards);
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
