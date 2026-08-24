<?php

declare(strict_types=1);

namespace App\Game\Event;

use App\Game\Model\GameState;
use App\Game\Model\Player;

/**
 * Turns a GameEvent into the GameState mutation it represents. This is the
 * only place GameState is allowed to change - game modes describe what
 * happened via events (GameContext::dispatch()) instead of mutating state
 * directly.
 */
final class GameEventApplier implements GameEventApplierInterface
{
    public function apply(GameEvent $event, GameState $state): ApplyResult
    {
        return match (true) {
            $event instanceof CardDrawnEvent => $this->applyCardDrawn($event, $state),
            $event instanceof SuitChangedEvent => new ApplyResult($state->withData('suit', $event->suit)),
            $event instanceof PlayOrderReversedEvent => new ApplyResult($state->withPlayerOrder(array_reverse($state->getPlayers()), true)),
            // Pure notifications: turn advance / current cards / lastPlayer /
            // fastPlay bookkeeping around them is applied directly by the
            // game mode (not every state change needs a dedicated event -
            // these carry no information a front-end or persisted log needs).
            $event instanceof CardPlayedEvent,
            $event instanceof TurnSkippedEvent,
            $event instanceof CardOrNothingCalledEvent,
            $event instanceof RoundEndedEvent => new ApplyResult($state),
            default => throw new \LogicException(\sprintf('No applier registered for event "%s"', $event::class)),
        };
    }

    private function applyCardDrawn(CardDrawnEvent $event, GameState $state): ApplyResult
    {
        [$state, $drawnCards] = $state->withDrawnCards($event->count);

        $player = current(array_filter(
            $state->getPlayers(),
            fn (Player $p): bool => $p->id === $event->player->id,
        ));
        $state = $state->withUpdatedPlayer($player->withCardsCount($player->cardsCount + count($drawnCards)));

        return new ApplyResult($state, $drawnCards);
    }
}
