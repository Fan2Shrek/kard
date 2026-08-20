<?php

namespace App\Game\Mode;

use App\Game\Model\Card\Card;
use App\Game\Model\Card\Hand;
use App\Game\Model\GameContext;
use App\Game\Model\GameState;

interface GameModeInterface
{
    /**
     * @param array<Card>          $cards
     * @param array<string, mixed> $data
     */
    public function play(array $cards, GameContext $context, Hand $hand, array $data = []): void;

    public function getGameMode(): GameModeEnum;

    /**
     * @return int|null The number of cards, or draw all cards
     */
    public function getCardsCount(int $playerCount): ?int;

    /**
     * @param array<string, Hand> $players
     *
     * @return array<string>
     */
    public function getPlayerOrder(array $players): array;

    public function isGameFinished(GameState $gameContext): bool;
}
