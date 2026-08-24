<?php

namespace App\Game\Mode;

use App\Enum\Card\Rank;
use App\Enum\Card\Suit;
use App\Game\Event\CardOrNothingCalledEvent;
use App\Game\Event\CardPlayedEvent;
use App\Game\Event\RoundEndedEvent;
use App\Game\Event\TurnSkippedEvent;
use App\Game\Model\Card\Card;
use App\Game\Model\Card\Hand;
use App\Game\Model\GameContext;
use App\Game\Model\State\GameState;

/**
 * @see https://bicyclecards.com/how-to-play/presidents
 */
final class PresidentGameMode extends AbstractGameMode
{
    use CardsHelperTrait;

    private bool $isTurnFinished;

    private GameContext $context;

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

	public function refreshScore(GameContext $ctx): void
	{
		foreach ($ctx->gameState->players as $player) {
			if ($player->score !== $player->hand->count()) {
				$ctx->pushScoreUpdate($player->id, $player->hand->count());
			}
		}
	}

    protected function doPlay(array $cards, GameContext $context, Hand $hand, array $data): GameState
    {
        $this->cards = $cards;
        $this->context = $context;

        if (\count($cards) > 3) {
            throw $this->createRuleException('card.count.invalid');
        }

        $this->gameContext = $this->context->mutate(fn (GameState $s): GameState => $s->withEveryoneCanPlay(false)); // reset
        $nonSkippedTurns = $this->gameContext->getRound()->getNonSkippedTurns();
        $currentCards = ($nonSkippedTurns[0] ?? null)?->getCards() ?? [];

        if ([] === $cards) {
            if ([] === $this->gameContext->getRound()->getTurns()) {
                throw $this->createRuleException('turn.first.at_least_one_card');
            }

            // skip
            $passingPlayer = $this->gameContext->getCurrentPlayer();
            $this->gameContext = $this->context->mutate(fn (GameState $s): GameState => $s->withCurrentCards([])->withNextPlayer());
            $this->context->dispatch(new TurnSkippedEvent($this->gameContext->getRoom(), $passingPlayer));

            if ($this->gameContext->getCurrentPlayer()->id === $this->gameContext->getLastPlayerId()) {
                $this->handleRoundEnd();
            }

            return $this->gameContext;
        }

        $actingPlayer = $this->gameContext->getCurrentPlayer();

        match (\count($currentCards)) { // @phpstan-ignore-line
            0 => $this->handleStart($cards),
            1 => $this->handleOneCard($cards, $currentCards),
            2 => $this->handleTwoCards($cards, $currentCards),
            3 => $this->handleThreeCards($cards, $currentCards),
        };

        $hand->removeCards($cards);

        $this->context->dispatch(new CardPlayedEvent($this->gameContext->getRoom(), $actingPlayer, $cards));

        if ($this->isTurnFinished ?? false) {
            return $this->gameContext;
        }

        $this->gameContext = $this->context->mutate(fn (GameState $s): GameState => $s
            ->withCurrentCards($cards)
            ->withLastPlayer($actingPlayer->id)
            ->withNextPlayer());

        return $this->gameContext;
    }

    /**
     * @param Card[] $cards
     * @param Card[] $currentCards
     */
    private function handleOneCard(array $cards, array $currentCards): void
    {
        if (1 !== count($cards)) {
            throw $this->createRuleException('card.count.invalid');
        }

        $card = $cards[0];

        if (!$this->isHigherByRankOrder($card, $currentCards[0]) && !$this->isSameRank($card, $currentCards[0])) {
            throw $this->createRuleException('card.value.higher');
        }

        if ([] === $this->gameContext->getCurrentCards()) {
            return;
        }

        $nonSkippedTurns = $this->gameContext->getRound()->getNonSkippedTurns();
        $lastTurn = $currentCards;
        $beforeLastTurn = ($nonSkippedTurns[1] ?? null)?->getCards() ?? null;

        if ($this->isSameRank($card, $currentCards[0])) {
            $isCallForFour = null !== $beforeLastTurn && $lastTurn[0]->rank === $beforeLastTurn[0]->rank;

            $this->context->dispatch(new CardOrNothingCalledEvent(
                $this->gameContext->getRoom(),
                $this->gameContext->getCurrentPlayer(),
                $card->rank,
                $isCallForFour,
            ));
        }

        // Rank or nothing :p
        if ($beforeLastTurn && $lastTurn[0]->rank === $beforeLastTurn[0]->rank) {
            // assert skip turn
            if ($card->rank !== $lastTurn[0]->rank) {
                throw $this->createRuleException('card.or_nothing', ['%played_card%' => $card->rank->value, '%actual_card%' => $lastTurn[0]->rank->value]);
            }

            // verify if square
            $rank = $lastTurn[0]->rank;
            $count = array_filter($nonSkippedTurns, fn ($turn): bool => $rank === $turn->getCards()[0]->rank);

            if (3 === count($count)) {
                $this->handleRoundEnd();
            } else {
                $this->gameContext = $this->context->mutate(fn (GameState $s): GameState => $s->withEveryoneCanPlay(true));
            }
        }

        if (Rank::TWO === $card->rank) {
            $this->handleRoundEnd();
        }
    }

    /**
     * @param Card[] $cards
     */
    private function handleStart(array $cards): void
    {
        if (!$this->allSameRank($cards)) {
            throw $this->createRuleException('card.values.not_same');
        }

        [$card] = $cards;

        if (Rank::TWO === $card->rank) {
            $this->handleRoundEnd();
        }

        if (2 === count($cards)) {
            $this->gameContext = $this->context->mutate(fn (GameState $s): GameState => $s->withEveryoneCanPlay(true));

            return;
        }
    }

    /**
     * @param Card[] $cards
     * @param Card[] $currentCards
     */
    private function handleTwoCards(array $cards, array $currentCards): void
    {
        if (2 !== count($cards)) {
            throw $this->createRuleException('card.count.invalid');
        }

        if (!$this->allSameRank($cards)) {
            throw $this->createRuleException('card.values.not_same');
        }

        [$card] = $cards;
        [$currentCard] = $currentCards;

        if (Rank::TWO === $card->rank) {
            $this->handleRoundEnd();
        }

        if ($this->allSameRank(array_merge($cards, $currentCards))) {
            $this->handleRoundEnd();

            return;
        }

        if (!$this->isHigherByRankOrder($card, $currentCard)) {
            throw $this->createRuleException('card.values.higher');
        }

        $this->gameContext = $this->context->mutate(fn (GameState $s): GameState => $s->withEveryoneCanPlay(true));
    }

    /**
     * @param Card[] $cards
     * @param Card[] $currentCards
     */
    private function handleThreeCards(array $cards, array $currentCards): void
    {
        if (3 !== count($cards)) {
            throw $this->createRuleException('card.count.invalid');
        }

        if (!$this->allSameRank($cards)) {
            throw $this->createRuleException('card.values.not_same');
        }

        [$card] = $cards;
        [$currentCard] = $currentCards;

        if (Rank::TWO === $card->rank) {
            $this->handleRoundEnd();
        }

        if ($this->allSameRank(array_merge($cards, $currentCards))) {
            return;
        }

        if (!$this->isHigherByRankOrder($card, $currentCard)) {
            throw $this->createRuleException('card.values.higher');
        }
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

    private function handleRoundEnd(): void
    {
        $this->gameContext = $this->context->mutate(fn (GameState $s): GameState => $s->withCurrentCards($this->cards)->withNewRound());
        $this->context->dispatch(new RoundEndedEvent($this->gameContext->getRoom()));
        $this->isTurnFinished = true;
    }
}
