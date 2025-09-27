<?php

declare(strict_types=1);

namespace App\Tests\There\Resources;

use App\Entity\Room;
use App\Enum\GameStatusEnum;
use App\Tests\There\ThereIs;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @extends AbstractBuilder<Room>
 */
final class RoomBuilder extends AbstractBuilder
{
    private GameStatusEnum $status = GameStatusEnum::WAITING;

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

    protected function getParams(): array
    {
        return [
            'gameMode' => ThereIs::aGameMode()->build(),
            'status' => $this->status,
        ];
    }

    protected function afterBuild(object $entity): void
    {
        $entity->setOwner(ThereIs::aUser()->build());
    }
}
