<?php

declare(strict_types=1);

namespace App\Game\Model\State;

final class Turn
{
    /**
     * @param string[]             $cardIds
     * @param array<string, mixed> $data
     */
    public function __construct(
        public string $playerId,
        public array $cardIds,
        public array $data = [],
    ) {
    }

    public function hasBeenSkipped(): bool
    {
        return [] === $this->cardIds;
    }
}
