<?php

declare(strict_types=1);

namespace App\Entity\Game;

use App\Entity\Room;
use App\Game\State\GameState;
use App\Game\State\PlayerState;
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
    private PlayerState $player1State;

    #[ORM\Column(type: Types::JSON)]
    private PlayerState $player2State;

    public function __construct(string $id, PlayerState $player1State, PlayerState $player2State)
    {
        $this->id = $id;

        $this->createdAt = new \DateTimeImmutable();
        $this->player1State = $player1State;
        $this->player2State = $player2State;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getPlayer1State(): PlayerState
    {
        return $this->player1State;
    }

    public function getPlayer2State(): PlayerState
    {
        return $this->player2State;
    }

    public static function createFromRoomAndGameState(Room $room, GameState $gameState): self
    {
        return new self($room->getId()->toString(), $gameState->player1, $gameState->player2);
    }

    public function toGameState(): GameState
    {
        return new GameState($this->player1State, $this->player2State, null);
    }
}
