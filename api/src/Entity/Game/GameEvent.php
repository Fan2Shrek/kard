<?php

declare(strict_types=1);

namespace App\Entity\Game;

use App\Enum\GameEventTypeEnum;
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

    #[ORM\Column(enumType: GameEventTypeEnum::class)]
    private GameEventTypeEnum $type;

    #[ORM\Column(type: Types::JSON)]
    private array $data = [];

    public function __construct(GameEventTypeEnum $type, array $data)
    {
        $this->type = $type;
        $this->data = $data;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): GameEventTypeEnum
    {
        return $this->type;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public static function createFromGameEvent(StateGameEvent $gameEvent): self
    {
        return new self($gameEvent->type, $gameEvent->data);
    }
}
