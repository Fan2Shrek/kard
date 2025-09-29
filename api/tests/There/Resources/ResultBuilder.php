<?php

declare(strict_types=1);

namespace App\Tests\There\Resources;

use App\Entity\Result;
use App\Entity\Room;
use App\Entity\User;
use App\Tests\There\ThereIs;

final class ResultBuilder extends AbstractBuilder
{
	private ?User $winner = null;
	private ?Room $room = null;

	public function __construct($container)
	{
		parent::__construct($container, Result::class);
	}

	public function getParams(): array
	{
		return [
			'winner' => $this->winner ??= ThereIs::aUser()->build(),
			'room' => $this->room ??= ThereIs::aRoom()->withStatus('finished')->withOwner($this->winner)->build(),
		];
	}

	public function withWinner(User $winner): self
	{
		$this->winner = $winner;

		return $this;
	}

	public function withRoom(Room $room): self
	{
		$this->room = $room;

		return $this;
	}
}
