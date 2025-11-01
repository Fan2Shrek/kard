<?php

declare(strict_types=1);

namespace App\Api\DTO;

use App\Entity\Room;
use App\Model\Card\Card;

class Play implements CurrentResourceAwareInterface
{
	private Room $room;

	/**
	 * @param Card[] $cards
	 */
	public function __construct(
		public readonly array $cards = [],
		public readonly array $data = [],
	) {
	}

	public function setCurrentResource(object $resource): void
	{
		$this->room = $resource;
	}

	public function getCurrentResource(): object
	{
		return $this->room;
	}
}
