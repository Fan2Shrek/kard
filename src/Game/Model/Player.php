<?php

namespace App\Game\Model;

use App\Entity\User;

final readonly class Player
{
    public function __construct(
        public string $id,
        public string $username,
        public int $cardsCount = 0,
    ) {
    }

    public static function fromUser(User $user): self
    {
        return new self($user->getId()->toString(), $user->getUsername());
    }

    public function withCardsCount(int $cardsCount): self
    {
        return new self($this->id, $this->username, $cardsCount);
    }
}
