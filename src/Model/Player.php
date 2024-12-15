<?php

namespace App\Model;

use App\Entity\User;

final class Player
{
    public function __construct(
        public readonly string $id,
        public readonly string $username,
    ) {
    }

    public static function fromUser(User $user): self
    {
        return new self($user->getId()->toString(), $user->getUsername());
    }
}
