<?php

namespace App\Game\Mode;

use App\Game\Model\Card\Card;
use App\Game\Model\GameContext;
use App\Game\Model\State\GameState;

interface GameModeInterface
{
    /**
     * @param array<string>        $cards card ids - resolved to Card by AbstractGameMode::play() before doPlay()
     * @param array<string, mixed> $data
     */
    public function play(array $cards, GameContext $context, string $playerId, array $data = []): void;

    public function getGameMode(): GameModeEnum;

    /**
     * @return int|null The number of cards, or draw all cards
     */
    public function getCardsCount(int $playerCount): ?int;

    /**
     * @return array<string>
     */
    public function getPlayerOrder(GameState $state): array;

    public function isGameFinished(GameState $state): bool;

	public function refreshScore(GameContext $ctx): void;
}
