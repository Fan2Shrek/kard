<?php

namespace App\Service\GameManager;

use App\Entity\Room;
use App\Entity\User;
use App\Model\Card\Card;
use App\Service\GameContextProvider;
use App\Service\GameManager\GameMode\GameModeEnum;
use App\Service\GameManager\GameMode\GameModeInterface;
use Symfony\Component\Mercure\HubInterface;

final class GameManager
{
    // @param iterable<GameModeInterface> $gameModes
    public function __construct(
        private iterable $gameModes,
        private HubInterface $hub,
        private GameContextProvider $gameContextProvider,
    ) {
    }

    public function play(Room $room, User $player, Card $card): void
    {
        /* @todo */
        /* $gameMode = $this->getGameMode($room->getGameMode());  */
        $gameMode = $this->getGameMode(GameModeEnum::PRESIDENT);
        $ctx = $this->gameContextProvider->provide($room);
        
        $gameMode->play($card, $ctx);
    }

    public function getGameMode(GameModeEnum $gameMode): GameModeInterface
    {
        foreach ($this->gameModes as $gameMode) {
            if ($gameMode->getGameMode() === $gameMode) {
                return $gameMode;
            }
        }

        throw new \InvalidArgumentException('Game mode not found');
    }

    public function getGameModes(): iterable
    {
        return $this->gameModes;
    }
}
