<?php

declare(strict_types=1);

namespace App\Game\Service;

use App\Enum\GameEventTypeEnum;
use App\Game\Model\Event\GameEvent;
use App\Game\Model\State\GameState;
use App\Game\Model\State\Round;
use App\Game\Model\State\Turn;

final class GameEventApplier
{
	public function apply(GameEvent $event, GameState $gameState): GameState
	{
		$newState = match ($event->type) {
			GameEventTypeEnum::ROUND_STARTED => $this->handleRoundStarted($gameState),
			GameEventTypeEnum::TURN_PLAYED => $this->handleTurnPlayed($gameState, $event),
			GameEventTypeEnum::TURN_ENDED => $this->handleTurnEnded($gameState),
			GameEventTypeEnum::TURN_SKIPPED => $this->handleTurnSkipped($gameState),
			GameEventTypeEnum::CARD_DISCARDED => $this->handleCardDiscarded($gameState, $event),
			GameEventTypeEnum::CARD_DRAWN => $this->handleCardDrawn($gameState),
			GameEventTypeEnum::CARD_GIVEN => $this->handleCardGiven($gameState, $event),
			GameEventTypeEnum::SCORE_UPDATED => $this->handleScoreUpdated($gameState, $event),
			GameEventTypeEnum::ROUND_ENDED => $this->handleRoundEnded($gameState),
			GameEventTypeEnum::REVERSE_PLAYERS_ORDER => $this->handleReversePlayersOrder($gameState),
			GameEventTypeEnum::ROUND_RESET => $this->handleRoundReset($gameState),
			GameEventTypeEnum::CURRENT_PLAYER_SET => $this->handleCurrentPlayerSet($gameState, $event),

			GameEventTypeEnum::CARD_OR_NOTHING_CALLED,
			GameEventTypeEnum::SUIT_CHANGED,
			GameEventTypeEnum::CHALLENGE_RESULT => $gameState,
		};

		return $newState;
	}

	private function handleReversePlayersOrder(GameState $state): GameState
	{
		$order = array_reverse($state->playerOrder);

		return $state->withPlayerOrder($order);
	}

	private function handleRoundReset(GameState $state): GameState
	{
		return $state->withRounds([]);
	}

	private function handleCurrentPlayerSet(GameState $state, GameEvent $event): GameState
	{
		$playerId = $event->payload['playerId'] ?? null;

		if ($playerId === null) {
			throw new \RuntimeException('No playerId found in event payload.');
		}

		return $state->withCurrentPlayer($playerId);
	}

	private function handleCardDrawn(GameState $state): GameState
	{
		$cardToDraw = $state->drawPile->getNext();

		return $state->withDrawPile($state->drawPile->removeCard($cardToDraw));
	}

	private function handleCardGiven(GameState $state, GameEvent $event): GameState
	{
		$cardId = $event->payload['cardId'] ?? null;
		$toPlayerId = $event->payload['toPlayerId'] ?? null;

		if ($cardId === null || $toPlayerId === null) {
			throw new \RuntimeException('No cardId, fromPlayerId or toPlayerId found in event payload.');
		}

		$toPlayerState = $state->getPlayerStateById($toPlayerId);

		return $state->withPlayerState($toPlayerState->addCard($cardId));
	}

	private function handleRoundStarted(GameState $state): GameState
	{
		return $state->addRound(new Round($state->getCurrentRoundNumber(), []));
	}

	private function handleTurnPlayed(GameState $state, GameEvent $event): GameState
	{
		$cards = $event->payload['cards'] ?? [];
		$playerId = $event->payload['playerId'] ?? [];
		$data = $event->payload['data'] ?? [];
		$round = $state->getCurrentRound();

		if ($round === null) {
			throw new \RuntimeException('No round found in game state.');
		}

		$newRound = $round->addTurn(new Turn($playerId, $cards, $data));

		return $state->withUpdatedRound($newRound);
	}

	private function handleTurnEnded(GameState $state): GameState
	{
		$nextPlayerId = $state->getNextPlayerId();

		return $state->withCurrentPlayer($nextPlayerId);
	}

	private function handleTurnSkipped(GameState $state): GameState
	{
		$nextPlayerId = $state->getNextPlayerId();

		return $state->withCurrentPlayer($nextPlayerId);
	}

	private function handleCardDiscarded(GameState $state, GameEvent $event): GameState
	{
		$cardId = $event->payload['cardId'] ?? null;
		$playerId = $event->payload['playerId'] ?? null;

		if ($cardId === null || $playerId === null) {
			throw new \RuntimeException('No cardId found in event payload.');
		}

		$playerState = $state->getPlayerStateById($playerId);

		return $state->withPlayerState($playerState->discardCard($cardId));
	}

	private function handleRoundEnded(GameState $state): GameState
	{
		$round = $state->getCurrentRound();

		if ($round === null) {
			return $state;
		}

		$discardPile = $state->discardPile;

		foreach ($round->turns as $turn) {
			foreach ($turn->cardIds as $cardId) {
				$discardPile = $discardPile->addCard($cardId);
			}
		}

		return $state
			->withDiscardPile($discardPile)
			->withRounds([]);
	}

	private function handleScoreUpdated(GameState $state, GameEvent $event): GameState
	{
		$playerId = $event->payload['playerId'] ?? null;
		$newScore = $event->payload['score'] ?? null;

		if ($playerId === null || $newScore === null) {
			throw new \RuntimeException('No playerId or newScore found in event payload.');
		}

		$playerState = $state->getPlayerStateById($playerId);

		return $state->withPlayerState($playerState->withScore($newScore));
	}
}
