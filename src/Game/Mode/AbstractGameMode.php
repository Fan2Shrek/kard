<?php

declare(strict_types=1);

namespace App\Game\Mode;

use App\Game\Exception\RuleException;
use App\Game\Model\Card\Card;
use App\Game\Model\Card\Hand;
use App\Game\Model\GameContext;
use App\Game\Model\GameState;

abstract class AbstractGameMode implements GameModeInterface
{
    protected GameState $gameContext;

    /**
     * @var array<Card>
     */
    protected array $cards;

    public function play(array $cards, GameContext $context, Hand $hand, array $data = []): void
    {
        $this->cards = $cards;
        $this->gameContext = $context->getState();

        $this->gameContext = $this->doPlay($cards, $context, $hand, $data);

        $context->replaceState($this->gameContext);
    }

    /**
     * This method implements the game rules.
     *
     * @param array<Card>          $cards
     * @param array<string, mixed> $data
     */
    abstract protected function doPlay(array $cards, GameContext $context, Hand $hand, array $data): GameState;

    /**
     * @param array<mixed> $params
     */
    protected function createRuleException(string $message, array $params = []): RuleException
    {
        $e = new RuleException($this->getGameMode(), $message);
        $e->setParams($params);

        return $e;
    }
}
