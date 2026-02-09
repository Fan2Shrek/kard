<?php

declare(strict_types=1);

namespace App\Game;

use App\Game\State\GameEvent;
use App\Game\State\GameState;
use App\Service\Game\GameModeProvider;

final class GameEventApplier
{
	public function __construct(
		private GameModeProvider $gameModeProvider,
	) {
	}

	public function apply(GameEvent $event, GameState $state): GameState
	{
		return match ($event->type) {
			GameEvent::CARD_PLAYED => $this->applyCardPlayed($event, $state),
			GameEvent::CARD_DRAW => $this->applyCardDraw($event, $state),
			default => throw new \InvalidArgumentException("Unknown event type: {$event->type}"),
		};
	}

	/**
	 * @param GameEvent[] $events
	 */
	public function applyMultiple(array $events, GameState $state): GameState
	{
		foreach ($events as $event) {
			$state = $this->apply($event, $state);
		}

		return $state;
	}

	private function applyCardPlayed(GameEvent $event, GameState $state): GameState
	{
		if (!$gameMode = ($event->data['gameMode'] ?? null)) {
			throw new \InvalidArgumentException("Missing gameMode in CARD_PLAYED event data");
		}

		$gameMode = $this->gameModeProvider->getForValue($gameMode);

		$cards = $event->data['cards'] ?? null;

		// refactor GameModes here

		return $state;
	}

	private function applyCardDraw(GameEvent $event, GameState $state): GameState
	{
		$playerId = $event->data['playerId'] ?? null;

		if (!\is_string($playerId)) {
			throw new \InvalidArgumentException("Missing playerId in CARD_DRAW event data");
		}

		$playerState = $state->getPlayerStateById($playerId);
		$drawPill = $state->drawPill;

		if ([] === $drawPill) {
			throw new \RuntimeException("No more cards to draw");
		}

        $drawn = array_shift($drawPill);
        $newPlayer = $playerState->withNewHand([...$playerState->hand, $drawn]);

		return $state
			->withUpdatedPlayer($newPlayer)
			->withUpdatedDrawPill($drawPill)
		;
	}
}
