<?php

declare(strict_types=1);

namespace App\Game\Model;

use App\Enum\GameEventTypeEnum;
use App\Game\Model\Card\DrawPile;
use App\Game\Model\Event\GameEvent;
use App\Game\Model\State\GameState;

class GameContext
{
    /**
     * @var GameEvent[]
     */
    private array $events = [];

    // tracks cards already "virtually" drawn within this batch - $gameState
    // itself never reflects events pushed earlier in the same doPlay() call,
    // so a second drawCard() would otherwise see the same top card again
    private DrawPile $pendingDrawPile;

    public function __construct(
        public GameState $gameState,
    ) {
        $this->pendingDrawPile = $gameState->drawPile;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function pushEvent(GameEventTypeEnum $type, array $payload = []): void
    {
        $this->events[] = new GameEvent($type, $payload);
    }

    /**
     * @return GameEvent[]
     */
    public function flushEvents(): array
    {
        $events = $this->events;
        $this->events = [];

        return $events;
    }

    public function pushScoreUpdate(string $playerId, int $newScore): void
    {
        $this->pushEvent(GameEventTypeEnum::SCORE_UPDATED, [
            'playerId' => $playerId,
            'score' => $newScore,
        ]);
    }

    public function startNewRound(): void
    {
        $this->pushEvent(GameEventTypeEnum::ROUND_STARTED);
    }

    public function endCurrentRound(): void
    {
        $this->pushEvent(GameEventTypeEnum::ROUND_ENDED);
    }

    /**
     * @param array<string>        $cards
     * @param array<string, mixed> $data
     */
    public function pushTurn(array $cards, ?string $playerId = null, array $data = []): void
    {
        $this->pushEvent(GameEventTypeEnum::TURN_PLAYED, [
            'cards' => $cards,
            'playerId' => $playerId ?? $this->gameState->currentPlayerId,
            'data' => $data,
        ]);
    }

    public function pushEndTurn(): void
    {
        $this->pushEvent(GameEventTypeEnum::TURN_ENDED);
    }

    public function pushCardDiscarded(string $cardId, string $playerId): void
    {
        $this->pushEvent(GameEventTypeEnum::CARD_DISCARDED, [
            'cardId' => $cardId,
            'playerId' => $playerId,
        ]);
    }

    public function pushCardOrNothingCalled(string $rank): void
    {
        $this->pushEvent(GameEventTypeEnum::CARD_OR_NOTHING_CALLED, [
            'rank' => $rank,
        ]);
    }

    public function drawCard(string $playerId): void
    {
        $cardId = $this->pendingDrawPile->getNext();
        $this->pendingDrawPile = $this->pendingDrawPile->removeCard($cardId);

        $this->pushEvent(GameEventTypeEnum::CARD_DRAWN);
        $this->pushEvent(GameEventTypeEnum::CARD_GIVEN, [
            'cardId' => $cardId,
            'toPlayerId' => $playerId,
        ]);
    }

    public function skipNextPlayerTurn(): void
    {
        $this->pushEvent(GameEventTypeEnum::TURN_SKIPPED, [
            'playerId' => $this->gameState->getNextPlayerId(),
        ]);
    }

    public function reversePlayerOrder(): void
    {
        $this->pushEvent(GameEventTypeEnum::REVERSE_PLAYERS_ORDER);
    }
}
