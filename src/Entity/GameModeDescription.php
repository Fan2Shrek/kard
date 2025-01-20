<?php

namespace App\Entity;

use App\Repository\GameModeDescriptionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameModeDescriptionRepository::class)]
class GameModeDescription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private GameMode $gameMode;

    #[ORM\Column(length: 255)]
    private ?string $img = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    public function __construct(GameMode $gameMode)
    {
        $this->gameMode = $gameMode;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getGameMode(): GameMode
    {
        return $this->gameMode;
    }

    public function getImg(): ?string
    {
        return $this->img;
    }

    public function setImg(string $img): static
    {
        $this->img = $img;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }
}
