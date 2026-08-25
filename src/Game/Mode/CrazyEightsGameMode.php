<?php

declare(strict_types=1);

namespace App\Game\Mode;

use App\Enum\Card\Rank;
use App\Enum\Card\Suit;
use App\Enum\GameEventTypeEnum;
use App\Game\Model\Card\Card;
use App\Game\Model\GameContext;
use App\Game\Model\State\GameState;
use App\Game\Model\State\PlayerState;
use App\Game\Model\State\Round;

final class CrazyEightsGameMode extends AbstractGameMode implements SetupGameModeInterface
{
    use CardsHelperTrait;

    public function getGameMode(): GameModeEnum
    {
        return GameModeEnum::CRAZY_EIGHTS;
    }

    public function getCardsCount(int $playerCount): int
    {
        return 7;
    }

    public function setup(GameContext $ctx): void
    {
        $firstCard = $ctx->gameState->drawPile->getNext();
        $ctx->startNewRound();
        $ctx->pushEvent(GameEventTypeEnum::CARD_DRAWN);
        $ctx->pushTurn([$firstCard], 'game');
    }

    public function getPlayerOrder(GameState $state): array
    {
        // array_keys() would do here, but PHP silently casts numeric-looking
        // string keys to int - reading ->id directly keeps these real strings
        $ids = array_map(fn (PlayerState $player): string => $player->id, array_values($state->players));
        shuffle($ids);

        return $ids;
    }

    public function isGameFinished(GameState $gameContext): bool
    {
        foreach ($gameContext->players as $player) {
            if (0 === $player->score) {
                // todo winner
                /* $gameContext = $gameContext->withWinner($player); */

                return true;
            }
        }

        return false;
    }

    public function refreshScore(GameContext $ctx): void
    {
        foreach ($ctx->gameState->players as $player) {
            if ($player->score !== $player->hand->count()) {
                $ctx->pushScoreUpdate($player->id, $player->hand->count());
            }
        }
    }

    protected function doPlay(array $cards, GameContext $context, array $data): void
    {
        $round = $context->gameState->getCurrentRound();
        $stackTwos = (bool) $context->getData('stackTwos', false);
        $pendingPenalty = $stackTwos ? $this->getPendingDrawPenalty($round) : 0;

        if (empty($cards)) {
            $drawCount = $pendingPenalty > 0 ? $pendingPenalty : 1;

            for ($i = 0; $i < $drawCount; ++$i) {
                $context->drawCard($context->gameState->currentPlayerId);
            }

            if ($pendingPenalty > 0) {
                // records the penalty as resolved so it doesn't carry over to the
                // following player - see getPendingDrawPenalty()
                $context->pushTurn([], null, ['drawPenalty' => 0]);
            }

            return;
        }

        if (!$this->allSameRank($cards)) {
            throw $this->createRuleException('cards.same_rank');
        }

        $mainCard = $cards[0];

        if ($pendingPenalty > 0) {
            if (Rank::TWO !== $mainCard->rank) {
                throw $this->createRuleException('cards.must_counter_two');
            }

            $newPenalty = $pendingPenalty + 2 * count($this->playedCardIds);
            $context->pushTurn($this->playedCardIds, null, ['drawPenalty' => $newPenalty]);
            $this->autoResolvePenaltyIfNoCounter($context, $newPenalty);

            return;
        }

        $lastTurn = $this->getLastNonSkippedTurn($round);
        $lastCard = \end($lastTurn->cardIds);
        $lastCard = $context->gameState->getCardById($lastCard);

        $activeSuit = $this->getActiveSuit($round, $lastCard);

        if (Rank::EIGHT === $mainCard->rank || Rank::JOKER === $mainCard->rank) {
            if (!isset($data['suit'])) {
                throw $this->createRuleException('suit.not_set');
            }

            $suit = Suit::tryFrom($data['suit']);

            if (null === $suit) {
                throw $this->createRuleException('suit.invalid');
            }

            $context->pushEvent(GameEventTypeEnum::SUIT_CHANGED, [
                'suit' => $suit->value,
            ]);

            $context->pushTurn($this->playedCardIds, null, ['suit' => $suit->value]);

            if (Rank::JOKER === $mainCard->rank) {
                $nextPlayerId = $context->gameState->getNextPlayerId();

                for ($i = 0; $i < 4 * count($this->playedCardIds); ++$i) {
                    $context->drawCard($nextPlayerId);
                }

                $context->skipNextPlayerTurn();
            }

            return;
        }

        if ($lastCard->rank !== $mainCard->rank && $activeSuit !== $mainCard->suit) {
            throw $this->createRuleException('cards.same_rank_or_suit', ['%rank%' => $lastCard->rank->value, '%suit%' => $activeSuit?->getSymbol()]);
        }

        if (!\in_array($mainCard->rank, $this->getSpecialCardRank(), true)) {
            $context->pushTurn($this->playedCardIds);

            return;
        }

        if (Rank::JACK === $mainCard->rank) {
            $context->skipNextPlayerTurn();
        }

        if (Rank::ACE === $mainCard->rank) {
            2 === count($context->gameState->players) ? $context->skipNextPlayerTurn() : $context->reversePlayerOrder();
        }

        if (Rank::TWO === $mainCard->rank) {
            $penalty = 2 * count($this->playedCardIds);

            if ($stackTwos) {
                $context->pushTurn($this->playedCardIds, null, ['drawPenalty' => $penalty]);
                $this->autoResolvePenaltyIfNoCounter($context, $penalty);

                return;
            }

            $nextPlayerId = $context->gameState->getNextPlayerId();

            for ($i = 0; $i < $penalty; ++$i) {
                $context->drawCard($nextPlayerId);
            }

            $context->skipNextPlayerTurn();
        }

        $context->pushTurn($this->playedCardIds);
    }

    /**
     * The running total of cards the current player owes because of a chain of
     * TWOs played by their predecessors (stackTwos config only) - read off the
     * last turn's data, which is kept in sync by every TWO play/resolution.
     */
    private function getPendingDrawPenalty(?Round $round): int
    {
        return $round?->getLastTurn()?->data['drawPenalty'] ?? 0;
    }

    /**
     * If the player about to inherit the pending penalty has no TWO to counter
     * with, there's no decision for them to make - draw the penalty and skip
     * straight to the player after them, in this same play, instead of waiting
     * for them to explicitly pass.
     */
    private function autoResolvePenaltyIfNoCounter(GameContext $context, int $penalty): void
    {
        $nextPlayerId = $context->gameState->getNextPlayerId();

        if ($this->playerHasTwo($context, $nextPlayerId)) {
            return;
        }

        for ($i = 0; $i < $penalty; ++$i) {
            $context->drawCard($nextPlayerId);
        }

        $context->pushTurn([], $nextPlayerId, ['drawPenalty' => 0]);
        $context->pushEvent(GameEventTypeEnum::TURN_ENDED);
    }

    private function playerHasTwo(GameContext $context, string $playerId): bool
    {
        foreach ($context->gameState->getPlayerStateById($playerId)->hand->cards as $cardId) {
            if (Rank::TWO === $context->gameState->getCardById($cardId)->rank) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<Rank>
     */
    private function getSpecialCardRank(): array
    {
        return [
            Rank::EIGHT,
            Rank::JACK,
            Rank::TWO,
            Rank::ACE,
        ];
    }

    protected function getActiveSuit(Round $round, Card $card): ?Suit
    {
        $lastTurn = $round->getLastTurn();

        if (null === $lastTurn) {
            return null;
        }

        if (isset($lastTurn->data['suit'])) {
            return Suit::from($lastTurn->data['suit']);
        }

        return $card->suit;
    }
}
