<?php

namespace App\Game\Mode;

use App\Enum\Card\Rank;
use App\Enum\Card\Suit;
use App\Game\Model\Card\Card;
use App\Game\Model\GameContext;
use App\Game\Model\State\GameState;
use App\Game\Model\State\Round;

/**
 * @see https://bicyclecards.com/how-to-play/presidents
 */
final class PresidentGameMode extends AbstractGameMode implements SetupGameModeInterface
{
    use CardsHelperTrait;

    public function getGameMode(): GameModeEnum
    {
        return GameModeEnum::PRESIDENT;
    }

    public function getCardsCount(int $playerCount): ?int
    {
        return null;
    }

    public function getPlayerOrder(GameState $state): array
    {
        $order = [];

        $queenOfHeartsId = array_find_key($state->cards, fn (Card $card): bool => Rank::QUEEN === $card->rank && Suit::HEARTS === $card->suit);

        foreach ($state->players as $playerState) {
            if ($playerState->hand->has($queenOfHeartsId)) {
                array_unshift($order, $playerState->id);
            } else {
                $order[] = $playerState->id;
            }
        }

        return $order;
    }

    public function isGameFinished(GameState $state): bool
    {
        foreach ($state->players as $player) {
            if (0 === $player->score) {
                return true;
            }
        }

        return false;
    }

    public function setup(GameContext $ctx): void
    {
        $ctx->startNewRound();
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
        if (\count($cards) > 3) {
            throw $this->createRuleException('card.count.invalid');
        }

        $cards = $this->resolveJokerRanks($cards);

        $currentRound = $context->gameState->getCurrentRound();

        if (null === $currentRound) {
            throw new \RuntimeException('No round found in game state.');
        }

        // skip
        if ([] === $cards) {
            if ($currentRound->isNew()) {
                throw $this->createRuleException('turn.first.at_least_one_card');
            }

            $context->pushTurn([]);

            $trailingSkips = 1; // this pass itself
            foreach (array_reverse($currentRound->turns) as $turn) {
                if (!$turn->hasBeenSkipped()) {
                    break;
                }

                ++$trailingSkips;
            }

            $playerCount = count($context->gameState->playerOrder);

            if ($trailingSkips >= $playerCount - 1) {
                $this->handleRoundEnd($context);

                $this->shouldPushEndTurn = true;
            }

            return;
        }

        if (!$this->allSameRank($cards)) {
            throw $this->createRuleException('card.values.not_same');
        }

        $played = $cards[0];

        if ($currentRound->isNew()) {
            $context->pushTurn($this->playedCardIds);

            if (Rank::TWO === $played->rank) {
                $this->handleRoundEnd($context);
            }

            return;
        }

        $lastTurn = $this->getLastNonSkippedTurn($currentRound);

        $count = count($lastTurn->cardIds);

        if (count($cards) !== $count) {
            throw $this->createRuleException('card.count.invalid');
        }

        $base = $this->gameState->getCardById($lastTurn->cardIds[0]);

        $cardOrNothingRank = $this->getCardOrNothingRank($currentRound);

        if (null !== $cardOrNothingRank) {
            if ($played->rank !== $cardOrNothingRank) {
                throw $this->createRuleException('card.or_nothing', ['%played_card%' => $played->rank->value, '%actual_card%' => $cardOrNothingRank->value]);
            }
        } elseif (!$this->isHigherByRankOrder($played, $base) && $base->rank !== $played->rank) {
            throw $this->createRuleException('card.value.higher');
        }

        $context->pushTurn($this->playedCardIds);

        // this play just matched the previous turn's rank for the first time - the lock is born here
        if (null === $cardOrNothingRank && $base->rank === $played->rank) {
            $context->pushCardOrNothingCalled($played->rank->value);
        }

        // four of a kind ends the round
        if (2 === count($this->playedCardIds) && $base->rank === $played->rank) {
            $this->handleRoundEnd($context);
        }
        // four singles of the same rank played in a row (skips ignored) ends the round too
        if (1 === count($this->playedCardIds)) {
            $lastThreeTurns = $this->getLastNonSkippedTurns($currentRound, 3);
            $allSingleSameRank = 3 === count($lastThreeTurns);

            foreach ($lastThreeTurns as $turn) {
                if (1 !== count($turn->cardIds) || $this->gameState->getCardById($turn->cardIds[0])->rank !== $played->rank) {
                    $allSingleSameRank = false;
                    break;
                }
            }

            if ($allSingleSameRank) {
                $this->handleRoundEnd($context);
            }
        }

        if (Rank::TWO === $played->rank) {
            $this->handleRoundEnd($context);
        }
    }

    /**
     * When the last two non-skipped turns of the round played the same rank ("carte ou rien"),
     * only that exact rank may be played next. Skips in between don't break this - they're
     * simply ignored when looking for the last two real plays.
     */
    private function getCardOrNothingRank(Round $round): ?Rank
    {
        $turns = $this->getLastNonSkippedTurns($round, 2);

        if (2 !== count($turns)) {
            return null;
        }

        $first = $this->gameState->getCardById($turns[0]->cardIds[0])->rank;
        $second = $this->gameState->getCardById($turns[1]->cardIds[0])->rank;

        return $first === $second ? $first : null;
    }

    /**
     * @return array<int, Rank>
     */
    protected function getRanks(): array
    {
        return [
            Rank::THREE,
            Rank::FOUR,
            Rank::FIVE,
            Rank::SIX,
            Rank::SEVEN,
            Rank::EIGHT,
            Rank::NINE,
            Rank::TEN,
            Rank::JACK,
            Rank::QUEEN,
            Rank::KING,
            Rank::ACE,
            Rank::TWO,
        ];
    }

    private function handleRoundEnd(GameContext $context): void
    {
        $context->endCurrentRound();
        $context->startNewRound();

        $this->shouldPushEndTurn = false;
    }

    /**
     * Jokers played alone (or together with other jokers only) count as a Two.
     * Jokers played alongside a non-joker card take that card's rank instead.
     *
     * @param Card[] $cards
     *
     * @return Card[]
     */
    private function resolveJokerRanks(array $cards): array
    {
        $nonJoker = array_find($cards, fn (Card $card): bool => Rank::JOKER !== $card->rank);
        $rank = null !== $nonJoker ? $nonJoker->rank : Rank::TWO;

        return array_map(
            fn (Card $card): Card => Rank::JOKER === $card->rank ? new Card($card->id, $rank, $card->suit) : $card,
            $cards,
        );
    }
}
