<?php

namespace App\Entity;

use App\Doctrine\RoomConfigurationType;
use App\Enum\GameStatusEnum;
use App\Game\Model\Card\Hand;
use App\Game\Model\GameConfiguration;
use App\Game\Model\State\PlayerState;
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

    #[ORM\Column(type: RoomConfigurationType::NAME)]
    private GameConfiguration $configuration;

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

    public function addBot(string $id, PlayerState $bot): static
    {
        $this->bots[$id] = [
            'id' => $id,
            'username' => $bot->playerName,
            'isBot' => true,
        ];

        return $this;
    }

    /**
     * Drops the most recently added bot and returns its id, or null if none left.
     */
    public function removeLastBot(): ?string
    {
        if ([] === $this->bots) {
            return null;
        }

        $id = array_key_last($this->bots);
        unset($this->bots[$id]);

        return $id;
    }

    /**
     * @return array<string, array{
     * 	id: string,
     * 	username: string,
     * 	isBot: bool
     * }>
     */
    #[Ignore]
    public function getBots(): array
    {
        return $this->bots;
    }

    /**
     * Participants (real users) and bots, as a single uniform list.
     *
     * @return PlayerState[]
     */
    #[Ignore]
    public function getPlayers(): array
    {
        $players = array_map(
            fn (User $user): PlayerState => new PlayerState(
                $user->getId()->toString(),
                $user->getUsername(),
                0,
                new Hand([]),
            ),
            $this->participants->toArray(),
        );

        foreach ($this->bots as $id => $bot) {
            $players[] = new PlayerState($id, $bot['username'], 0, new Hand([]), true);
        }

        return array_values($players);
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

    public function getConfiguration(): GameConfiguration
    {
        return $this->configuration;
    }

    public function setConfiguration(GameConfiguration $configuration): static
    {
        $this->configuration = $configuration;

        return $this;
    }
}
