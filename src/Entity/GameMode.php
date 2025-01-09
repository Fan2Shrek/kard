<?php

namespace App\Entity;

use App\Repository\GameModeRepository;
use App\Service\GameManager\GameMode\GameModeEnum;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameModeRepository::class)]
class GameMode
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(enumType: GameModeEnum::class)]
    private ?GameModeEnum $value = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $active = true;

    public function __construct(GameModeEnum $value)
    {
        $this->value = $value;
    }

    public function getId(): ?int
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
}
