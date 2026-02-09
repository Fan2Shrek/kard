<?php

declare(strict_types=1);

namespace App\Entity\Game;

use App\Game\State\GameEvent as StateGameEvent;
use App\Repository\Game\GameEventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameEventRepository::class)]
final class GameEvent
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\Column]
    private string $type;

    #[ORM\Column(type: Types::JSON)]
    private array $data = [];

    #[ORM\Column]
    private string $roomId;

    public function __construct(string $type, array $data, string $roomId)
    {
        $this->type = $type;
        $this->data = $data;
		$this->roomId = $roomId;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getData(): array
    {
        return $this->data;
    }

	public function getRoomId(): string
	{
		return $this->roomId;
	}

    public static function createFromGameEvent(StateGameEvent $gameEvent): self
    {
        return new self($gameEvent->type, $gameEvent->data);
    }
}
