<?php

namespace App\Service\GameManager\GameMode;

use App\Model\Card\Card;
use App\Model\Card\Hand;
use App\Model\GameContext;

interface GameModeInterface
{
    /**
     * @param array<Card>          $cards
     * @param array<string, mixed> $data
     */
    public function play(array $cards, GameContext $gameContext, Hand $hand, array $data = []): void;

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

    public function isGameFinished(GameContext $gameContext): bool;
}
