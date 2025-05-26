<?php

declare(strict_types=1);

namespace App\Service\GameManager\GameMode;

use App\Model\Card\Card;
use App\Model\GameContext;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

abstract class AbstractGameMode implements GameModeInterface
{
    protected GameContext $gameContext;
    /**
     * @var array<Card>
     */
    protected array $cards;

    public function __construct(
        private HubInterface $hub,
    ) {
    }

    public function play(array $cards, GameContext $gameContext): void
    {
        $this->cards = $cards;
        $this->gameContext = $gameContext;

        $this->doPlay($cards, $gameContext);
    }

    /**
     * This method implements the game rules.
     *
     * @param array<Card> $cards
     */
    abstract protected function doPlay(array $cards, GameContext $gameContext): void;

    protected function dispatchMercureEvent(string $eventName, string $text): void
    {
        $this->hub->publish(new Update(
            \sprintf('room-%s', $this->gameContext->getId()),
            \json_encode([
                'action' => $eventName,
                'data' => [
                    'text' => $text,
                ],
            ])
        ));
    }
}
