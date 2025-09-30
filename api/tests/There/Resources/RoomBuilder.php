<?php

declare(strict_types=1);

namespace App\Tests\There\Resources;

use App\Entity\Room;
use App\Entity\User;
use App\Enum\GameStatusEnum;
use App\Tests\There\ThereIs;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @extends AbstractBuilder<Room>
 */
final class RoomBuilder extends AbstractBuilder
{
    private GameStatusEnum $status = GameStatusEnum::WAITING;
	private ?User $owner = null;
	private array $participants = [];

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container, Room::class);
    }

    public function withStatus(GameStatusEnum|string $status): self
    {
        if (\is_string($status)) {
            $status = GameStatusEnum::from($status);
        }

        $this->status = $status;

        return $this;
    }

	public function withOwner(User $owner): self
	{
		$this->owner = $owner;
		$this->addParticipant($owner);

		return $this;
	}

	public function addParticipant(User $participant): self
	{
		$this->participants[] = $participant;

		return $this;
	}

    protected function getParams(): array
    {
        return [
            'gameMode' => ThereIs::a()->GameMode()->build(),
            'status' => $this->status,
        ];
    }

    protected function afterBuild(object $entity): void
    {
        $entity->setOwner($this->owner ??= ThereIs::a()->User()->build());
		foreach ($this->participants as $participant) {
			$entity->addParticipant($participant);
		}
    }
}
