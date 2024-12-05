<?php

namespace App\Service\GameManager;

use App\Domain\Exception\RuleException;
use App\Entity\Room;
use App\Entity\User;
use App\Model\Card\Card;
use App\Service\GameContextProvider;
use App\Service\GameManager\GameMode\GameModeEnum;
use App\Service\GameManager\GameMode\GameModeInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Twig\Environment;

final class GameManager
{
    // @param iterable<GameModeInterface> $gameModes
    public function __construct(
        private iterable $gameModes,
        private HubInterface $hub,
        private GameContextProvider $gameContextProvider,
        private Environment $twig,
    ) {
    }

    /**
     * @param array<Card> $cards
     */
    public function play(Room $room, User $player, array $cards): void
    {
        /* @todo */
        /* $gameMode = $this->getGameMode($room->getGameMode());  */
        $gameMode = $this->getGameMode(GameModeEnum::PRESIDENT);
        $ctx = $this->gameContextProvider->provide($room);
        
        try {
            $gameMode->play($cards, $ctx);
        } catch (RuleException $e) {
            /** @todo do something */
            return;
        }

        $this->hub->publish(new Update(
            sprintf('/room/%s/%s', $room->getId(), $player->getId()),
            $this->renderView('components/turbo/cards-played.html.twig', [
                'cards' => $cards,
                'player' => $player,
            ])
        ));
    }

    public function getGameMode(GameModeEnum $gameModeEnum): GameModeInterface
    {
        foreach ($this->gameModes as $gameMode) {
            if ($gameMode->getGameMode() === $gameModeEnum) {
                return $gameMode;
            }
        }

        throw new \InvalidArgumentException('Game mode not found');
    }

    public function getGameModes(): iterable
    {
        return $this->gameModes;
    }

    private function renderView(string $template, array $data): string
    {
        return $this->twig->render($template, $data);
    }
}
