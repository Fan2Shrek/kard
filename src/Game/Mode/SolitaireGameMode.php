<?php

declare(strict_types=1);

namespace App\Game\Mode;

use App\Enum\Card\Rank;
use App\Enum\Card\Suit;
use App\Game\Model\Card\AbstractCardStack;
use App\Game\Model\Card\Card;
use App\Game\Model\Card\Pile;
use App\Game\Model\GameContext;
use App\Game\Model\State\GameState;
use App\Game\Service\GameEventApplier;

/**
 * Klondike, single player.
 *
 * Layout lives in GameState::$piles: seven columns split in a face-down and a
 * face-up half (tableau_N_down / tableau_N_up) plus four foundations, while the
 * stock and the waste reuse the draw and discard piles every other mode uses.
 */
final class SolitaireGameMode extends AbstractGameMode implements SetupGameModeInterface
{
    public const COLUMNS = 7;

    /**
     * Destination key asking the mode to play the game out on its own. Only
     * legal once nothing is face down - past that point the game is decided and
     * finishing it by hand is just clicking, not playing.
     */
    public const AUTO_FINISH = 'auto';

    private const RANK_ORDER = [
        Rank::ACE->value => 1,
        Rank::TWO->value => 2,
        Rank::THREE->value => 3,
        Rank::FOUR->value => 4,
        Rank::FIVE->value => 5,
        Rank::SIX->value => 6,
        Rank::SEVEN->value => 7,
        Rank::EIGHT->value => 8,
        Rank::NINE->value => 9,
        Rank::TEN->value => 10,
        Rank::JACK->value => 11,
        Rank::QUEEN->value => 12,
        Rank::KING->value => 13,
    ];

    public function getGameMode(): GameModeEnum
    {
        return GameModeEnum::SOLITAIRE;
    }

    public function getCardsCount(int $playerCount): int
    {
        return 0;
    }

    public function getPlayerOrder(GameState $state): array
    {
        return array_keys($state->players);
    }

    public function setup(GameContext $ctx): void
    {
        $stock = array_values($ctx->gameState->drawPile->cards);
        $offset = 0;

        for ($column = 0; $column < self::COLUMNS; ++$column) {
            $dealt = \array_slice($stock, $offset, $column + 1);
            $offset += $column + 1;

            $faceUp = array_pop($dealt);

            if ([] !== $dealt) {
                $ctx->moveCards(GameEventApplier::STOCK, self::downKey($column), $dealt);
            }

            $ctx->moveCards(GameEventApplier::STOCK, self::upKey($column), [$faceUp]);
        }
    }

    public function isGameFinished(GameState $state): bool
    {
        return \count(Suit::cases()) * 13 === $this->foundationCount($state);
    }

    public function refreshScore(GameContext $ctx): void
    {
        $ctx->pushScoreUpdate($ctx->gameState->currentPlayerId, $this->foundationCount($ctx->gameState));
    }

    protected function doPlay(array $cards, GameContext $context, array $data): void
    {
        $to = $data['to'] ?? throw $this->createRuleException('destination.missing');

        if (GameEventApplier::STOCK === $to) {
            $this->drawOrRecycle($context);

            return;
        }

        if (self::AUTO_FINISH === $to) {
            $this->autoFinish($context);

            return;
        }

        if ([] === $cards) {
            throw $this->createRuleException('move.no_card');
        }

        $from = $this->locate($context->gameState, $cards[0]);

        $this->assertMovable($context->gameState, $from, $cards);
        $this->assertLands($context->gameState, $to, $cards);

        $context->moveCards($from, $to, array_map(fn (Card $card): string => $card->id, $cards));

        $this->revealIfNeeded($context, $from, $cards);
    }

    /**
     * Solitaire has no hand and no opponent: the base class checks and the
     * discard/end-turn events it pushes are both meaningless here.
     */
    protected function validatePlay(array $cards, GameContext $context, string $playerId, array $data = []): void
    {
    }

    protected function postPlay(GameContext $context, string $playerId, array $data = []): void
    {
    }

    private static function downKey(int $column): string
    {
        return \sprintf('tableau_%d_down', $column);
    }

    private static function upKey(int $column): string
    {
        return \sprintf('tableau_%d_up', $column);
    }

    private static function foundationKey(Suit $suit): string
    {
        return \sprintf('foundation_%s', $suit->value);
    }

    /**
     * @return string[]
     */
    private static function columnKeys(): array
    {
        return array_map(self::upKey(...), range(0, self::COLUMNS - 1));
    }

    /**
     * Turns the top stock card face up on the waste, or puts the whole waste
     * back under the stock once it has run out.
     */
    private function drawOrRecycle(GameContext $context): void
    {
        $state = $context->gameState;

        if (0 === $state->drawPile->count()) {
            if (0 === $state->discardPile->count()) {
                throw $this->createRuleException('draw.empty');
            }

            // keep draw order stable across cycles, rather than flipping it each time the stock runs out
            $context->moveCards(GameEventApplier::WASTE, GameEventApplier::STOCK, array_values($state->discardPile->cards));

            return;
        }

        $context->moveCards(GameEventApplier::STOCK, GameEventApplier::WASTE, [$state->drawPile->getNext()]);
    }

    /**
     * Plays every remaining card onto the foundations, drawing and recycling the
     * stock as needed. Mirrors the applier's own bookkeeping locally because the
     * events pushed here are only applied to the state after doPlay() returns.
     */
    private function autoFinish(GameContext $context): void
    {
        $state = $context->gameState;

        for ($column = 0; $column < self::COLUMNS; ++$column) {
            if (0 !== $this->stack($state, self::downKey($column))->count()) {
                throw $this->createRuleException('auto.face_down_remaining');
            }
        }

        $foundations = [];
        foreach (Suit::cases() as $suit) {
            $pile = $this->stack($state, self::foundationKey($suit));
            $foundations[$suit->value] = $pile->count();
        }

        $columns = [];
        for ($column = 0; $column < self::COLUMNS; ++$column) {
            $columns[$column] = array_values($this->stack($state, self::upKey($column))->cards);
        }

        $waste = array_values($state->discardPile->cards);
        $stock = array_values($state->drawPile->cards);
        $barrenDraws = 0;

        // every card ends on a foundation, so 52 placements plus the draws needed
        // to reach them bounds this comfortably
        for ($step = 0; $step < 1000; ++$step) {
            if (52 === array_sum($foundations)) {
                return;
            }

            $moved = false;

            foreach ($columns as $column => $ids) {
                if ([] === $ids) {
                    continue;
                }

                $card = $state->getCardById($ids[\count($ids) - 1]);

                if (null === $card->suit || $foundations[$card->suit->value] + 1 !== self::rank($card)) {
                    continue;
                }

                array_pop($columns[$column]);
                ++$foundations[$card->suit->value];
                $context->moveCards(self::upKey($column), self::foundationKey($card->suit), [$card->id]);
                $moved = true;
            }

            if ([] !== $waste) {
                $card = $state->getCardById($waste[\count($waste) - 1]);

                if (null !== $card->suit && $foundations[$card->suit->value] + 1 === self::rank($card)) {
                    array_pop($waste);
                    ++$foundations[$card->suit->value];
                    $context->moveCards(GameEventApplier::WASTE, self::foundationKey($card->suit), [$card->id]);
                    $moved = true;
                }
            }

            if ($moved) {
                $barrenDraws = 0;

                continue;
            }

            if ([] === $stock) {
                if ([] === $waste) {
                    return;
                }

                $stock = array_reverse($waste);
                $context->moveCards(GameEventApplier::WASTE, GameEventApplier::STOCK, $stock);
                $waste = [];

                continue;
            }

            // a whole pass of the stock without a single placement means the rest
            // is unreachable - stop rather than cycle forever
            if (++$barrenDraws > \count($stock) + \count($waste)) {
                return;
            }

            $drawn = array_shift($stock);
            $waste[] = $drawn;
            $context->moveCards(GameEventApplier::STOCK, GameEventApplier::WASTE, [$drawn]);
        }
    }

    /**
     * @param Card[] $cards
     */
    private function assertMovable(GameState $state, string $from, array $cards): void
    {
        $source = array_values($this->stack($state, $from)->cards);
        $ids = array_map(fn (Card $card): string => $card->id, $cards);

        // only the top card of the stock, the waste or a foundation can leave it;
        // a tableau column also gives away any face-up run below that card
        if (!str_starts_with($from, 'tableau_') && 1 !== \count($ids)) {
            throw $this->createRuleException('move.single_card_only');
        }

        if ($ids !== \array_slice($source, -\count($ids))) {
            throw $this->createRuleException('move.not_on_top');
        }

        $this->assertSequence($cards);
    }

    /**
     * @param Card[] $cards
     */
    private function assertLands(GameState $state, string $to, array $cards): void
    {
        $target = $this->stack($state, $to);
        $topId = $target->cards[array_key_last($target->cards)] ?? null;
        $top = null === $topId ? null : $state->getCardById($topId);

        if (str_starts_with($to, 'foundation_')) {
            $card = $cards[0];

            if (null === $card->suit) {
                throw $this->createRuleException('foundation.no_suit');
            }

            if (1 !== \count($cards)) {
                throw $this->createRuleException('foundation.single_card_only');
            }

            if (self::foundationKey($card->suit) !== $to) {
                throw $this->createRuleException('foundation.wrong_suit');
            }

            $expected = null === $top ? 1 : self::RANK_ORDER[$top->rank->value] + 1;

            if (self::rank($card) !== $expected) {
                throw $this->createRuleException('foundation.sequence');
            }

            return;
        }

        if (!\in_array($to, self::columnKeys(), true)) {
            throw $this->createRuleException('destination.invalid');
        }

        $card = $cards[0];

        if (null === $top) {
            if (Rank::KING !== $card->rank) {
                throw $this->createRuleException('column.king_only');
            }

            return;
        }

        if (self::rank($card) !== self::rank($top) - 1 || self::isRed($card) === self::isRed($top)) {
            throw $this->createRuleException('column.sequence');
        }
    }

    /**
     * @param Card[] $cards
     */
    private function assertSequence(array $cards): void
    {
        for ($i = 1; $i < \count($cards); ++$i) {
            $previous = $cards[$i - 1];
            $card = $cards[$i];

            if (self::rank($card) !== self::rank($previous) - 1 || self::isRed($card) === self::isRed($previous)) {
                throw $this->createRuleException('move.run_invalid');
            }
        }
    }

    /**
     * @param Card[] $cards
     */
    private function revealIfNeeded(GameContext $context, string $from, array $cards): void
    {
        if (!preg_match('/^tableau_(\d+)_up$/', $from, $matches)) {
            return;
        }

        $column = (int) $matches[1];
        $remaining = $this->stack($context->gameState, $from)->count() - \count($cards);
        $down = $this->stack($context->gameState, self::downKey($column));

        if (0 !== $remaining || 0 === $down->count()) {
            return;
        }

        $revealed = array_values($down->cards);

        $context->moveCards(self::downKey($column), $from, [end($revealed)]);
    }

    private function locate(GameState $state, Card $card): string
    {
        if (\in_array($card->id, $state->discardPile->cards, true)) {
            return GameEventApplier::WASTE;
        }

        foreach ($state->piles as $key => $pile) {
            if (\in_array($card->id, $pile->cards, true)) {
                return $key;
            }
        }

        throw $this->createRuleException('move.card_not_in_play');
    }

    private function stack(GameState $state, string $key): AbstractCardStack
    {
        return match ($key) {
            GameEventApplier::STOCK => $state->drawPile,
            GameEventApplier::WASTE => $state->discardPile,
            default => $state->piles[$key] ?? new Pile(),
        };
    }

    private function foundationCount(GameState $state): int
    {
        $count = 0;

        foreach (Suit::cases() as $suit) {
            $count += ($state->piles[self::foundationKey($suit)] ?? new Pile())->count();
        }

        return $count;
    }

    private static function rank(Card $card): int
    {
        return self::RANK_ORDER[$card->rank->value] ?? throw new \InvalidArgumentException('Jokers have no place in solitaire');
    }

    private static function isRed(Card $card): bool
    {
        return \in_array($card->suit, [Suit::HEARTS, Suit::DIAMONDS], true);
    }
}
