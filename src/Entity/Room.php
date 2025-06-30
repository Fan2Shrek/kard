<?php

namespace App\Entity;

use App\Enum\GameStatusEnum;
use App\Model\Player;
use App\Repository\RoomRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Attribute\Ignore;

#[ORM\Entity(repositoryClass: RoomRepository::class)]
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

    /**
     * @var array<string, array{
     * 	id: string,
     * 	username: string,
     * 	isBot: bool
     * }>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $bots = [];

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
     * string $id require for seriliazer somehow.
     */
    public function addBot(string $id, Player $bot): static
    {
        $this->bots[$id] = [
            'id' => $id,
            'username' => $bot->username,
            'isBot' => true,
        ];

        return $this;
    }

    /**
     * @return array<string, array{
     * 	id: string,
     * 	username: string,
     * 	isBot: bool
     * }>
     * public function getBots(): array
     * {
     * return $this->bots;
     * }
     * @return Player[]
     */
    #[Ignore]
    public function getPlayers(): array
    {
        return array_values(array_merge(
            array_map(
                fn (User $user): Player => Player::fromUser($user),
                $this->participants->toArray(),
            ),
            array_reduce(
                array_keys($this->bots),
                function (array $acc, string $id) {
                    $acc[$id] = new Player(...$this->bots[$id]);

                    return $acc;
                },
                [],
            ),
        ));
    }

    public function addParticipant(User $player): static
    {
        if (!$this->participants->contains($player)) {
            $this->participants->add($player);
        }

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
