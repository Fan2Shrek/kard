<?php

namespace App\Entity;

use App\Repository\ResultRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity(repositoryClass: ResultRepository::class)]
class Result
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    protected UuidInterface $id;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $winner = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private GameMode $gameMode;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $date;

    public function __construct(User $winner, GameMode $gameMode, \DateTimeInterface $date)
    {
        $this->winner = $winner;
        $this->gameMode = $gameMode;
        $this->date = $date;
    }

    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    public function getWinner(): ?User
    {
        return $this->winner;
    }

    public function getGameMode(): ?GameMode
    {
        return $this->gameMode;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }
}
