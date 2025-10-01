<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Api\State\Provider\GameModeProvider;
use App\Repository\GameModeRepository;
use App\Service\GameManager\GameMode\GameModeEnum;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameModeRepository::class)]
#[ApiResource(operations: [
    new GetCollection(
        provider: GameModeProvider::class,
    ),
])]
class GameMode
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(enumType: GameModeEnum::class)]
    private ?GameModeEnum $value = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $active = true;

    public function __construct(GameModeEnum $value)
    {
        $this->value = $value;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getValue(): ?GameModeEnum
    {
        return $this->value;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function __toString(): string
    {
        return $this->value->value;
    }
}
