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
    private User $winner;

    // Compatibility, remove someday
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?GameMode $gameMode = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Room $room;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $date;

    // solitaire records how the game was won; null for modes that don't track it
    #[ORM\Column(nullable: true)]
    private ?int $moves = null;

    #[ORM\Column(nullable: true)]
    private ?int $durationSeconds = null;

    public function __construct(User $winner, Room $room, ?int $moves = null, ?int $durationSeconds = null)
    {
        $this->winner = $winner;
        $this->room = $room;
        $this->date = new \DateTimeImmutable();
        $this->moves = $moves;
        $this->durationSeconds = $durationSeconds;
    }

    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    public function getWinner(): ?User
    {
        return $this->winner;
    }

    public function getRoom(): Room
    {
        return $this->room;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function getMoves(): ?int
    {
        return $this->moves;
    }

    public function getDurationSeconds(): ?int
    {
        return $this->durationSeconds;
    }
}
