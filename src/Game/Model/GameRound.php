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

    /**
     * Turns where cards were actually played (i.e. not a pass), most recent first.
     *
     * @return Turn[]
     */
    public function getNonSkippedTurns(): array
    {
        return array_values(array_filter(
            array_reverse($this->turns),
            fn (Turn $turn): bool => [] !== $turn->getCards(),
        ));
    }
}
