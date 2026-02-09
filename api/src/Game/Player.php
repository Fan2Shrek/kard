<?php

declare(strict_types=1);

namespace App\Game;

use App\Entity\User;

final readonly class Player
{
	public function __construct(
		public string $id,
		public string $name,
	) {
	}

	public function fromUser(User $user): self
	{
		return new self($user->getId()->toString(), $user->getUsername());
	}
}
