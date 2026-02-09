<?php

declare(strict_types=1);

namespace App\Service\Game\State;

use App\Entity\Game\InitialGameState;
use App\Entity\Room;
use App\Game\State\GameState;
use App\Repository\Game\InitialGameStateRepository;

final class DoctrineGameStateRepository implements GameStateRepositoryInterface
{
    public function __construct(
        private InitialGameStateRepository $initialGameStateRepository,
    ) {}

    public function save(GameState $gameState, Room $room): void
    {
        $gameState = InitialGameState::createFromRoomAndGameState($room, $gameState);

        $this->initialGameStateRepository->save($gameState);
    }

    public function get(Room $room): ?GameState
    {
        $intialGameState = $this->initialGameStateRepository->find($room->getId()->toString());

        return $intialGameState?->toGameState();
    }
}
