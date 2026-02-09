<?php

declare(strict_types=1);

namespace App\Entity\Game;

use App\Entity\Room;
use App\Game\State\GameState;
use App\Game\State\PlayerState;
use App\Model\Card\Card;
use App\Repository\Game\InitialGameStateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InitialGameStateRepository::class)]
class InitialGameState
{
	#[ORM\Id]
	#[ORM\Column]
	private string $id;

	#[ORM\Column(type: Types::DATE_IMMUTABLE)]
	private \DateTimeImmutable $createdAt;

	#[ORM\Column(type: Types::JSON)]
	private array $playerStates;

	#[ORM\Column(type: Types::JSON)]
	private array $drawPill;

	/**
	 * @param PlayerState[] $players
	 * @param Card[] $drawPill
	 * */
	public function __construct(string $id, array $players, array $drawPill)
	{
		$this->id = $id;

		$this->createdAt = new \DateTimeImmutable();
		$this->playerStates = $players;
		$this->drawPill = $drawPill;
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function getCreatedAt(): \DateTimeImmutable
	{
		return $this->createdAt;
	}

	public static function createFromRoomAndGameState(Room $room, GameState $gameState): self
	{
		return new self($room->getId()->toString(), $gameState->players, $gameState->drawPill);
	}

	public function toGameState(): GameState
	{
		return new GameState($this->playerStates, 0, drawPill: $this->drawPill);
	}
}
