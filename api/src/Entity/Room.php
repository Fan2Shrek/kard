<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\ExactFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use App\Enum\GameStatusEnum;
use App\Model\Player;
use App\Repository\RoomRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Ramsey\Uuid\UuidInterface;

#[ApiResource(
    operations: [
        new GetCollection(
            parameters: [
                'status' => new QueryParameter(filter: new ExactFilter()),
            ],
        ),
    ],
)]
#[ORM\Entity(repositoryClass: RoomRepository::class)]
// todo add owner in __construct
class Room
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    protected UuidInterface $id;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class)]
    private Collection $participants;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private GameMode $gameMode;

    #[ORM\Column(enumType: GameStatusEnum::class)]
    private GameStatusEnum $status;

    public function __construct(GameMode $gameMode, UuidInterface|string|null $id = null, ?GameStatusEnum $status = null)
    {
        if (\is_string($id)) {
            $id = UuidV4::fromString($id);
        }

        if (null !== $id) {
            $this->id = $id;
        }

        $this->participants = new ArrayCollection();
        $this->gameMode = $gameMode;
        $this->status = $status ?? GameStatusEnum::WAITING;
    }

    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    /**
     * @return Player[]
     */
    public function getPlayers(): array
    {
        return array_map(
            fn (User $user): Player => Player::fromUser($user),
            $this->participants->toArray(),
        );
    }

    public function addParticipant(User $player): static
    {
        if (!$this->participants->contains($player)) {
            $this->participants->add($player);
        }

        return $this;
    }

    // weird naming due to serialization b*llsh*t
    public function removeParticipantBlaBlaBla(User $player): static
    {
        $this->participants->removeElement($player);

        return $this;
    }

    public function getGameMode(): ?GameMode
    {
        return $this->gameMode;
    }

    public function getStatus(): ?GameStatusEnum
    {
        return $this->status;
    }

    public function setStatus(GameStatusEnum $status): static
    {
        $this->status = $status;

        return $this;
    }
}
