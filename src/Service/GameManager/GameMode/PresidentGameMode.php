<?php

namespace App\Service\GameManager\GameMode;

use App\Enum\Card\Rank;
use App\Enum\Card\Suit;
use App\Model\Card\Card;
use App\Model\Card\Hand;
use App\Model\GameContext;

/**
 * @see https://bicyclecards.com/how-to-play/presidents
 *
 * @todo gameData in gameContext ??
 */
final class PresidentGameMode extends AbstractGameMode
{
    use CardsHelperTrait;

    private bool $isTurnFinished;

    public function getGameMode(): GameModeEnum
    {
        return GameModeEnum::PRESIDENT;
    }

    public function getCardsCount(int $playerCount): ?int
    {
        return null;
    }

    public function getPlayerOrder(array $players): array
    {
        $order = [];

        foreach ($players as $id => $hand) {
            if ($hand->has(new Card(Suit::HEARTS, Rank::QUEEN))) {
                array_unshift($order, $id);
            } else {
                $order[] = $id;
            }
        }

        return $order;
    }

    public function isGameFinished(GameContext $gameContext): bool
    {
        foreach ($gameContext->getPlayers() as $player) {
            if (0 === $player->cardsCount) {
                $gameContext->setWinner($player);

                return true;
            }
        }

        return false;
    }

    protected function doPlay(array $cards, GameContext $gameContext, Hand $hand, array $data): void
    {
        $this->cards = $cards;
        $this->gameContext = $gameContext;

        if (\count($cards) > 3) {
            throw $this->createRuleException('card.count.invalid');
        }

        $gameContext->addData('fastPlay', false); // reset
        $currentCards = $gameContext->getCurrentCards();
        $previousTurns = array_reverse($this->gameContext->getRound()->getTurns());
        $nonSkippedTurns = array_values(array_filter($previousTurns, fn ($turn): bool => !empty($turn->getCards())));

        if (0 === \count($nonSkippedTurns)) {
            $currentCards = [];
        } else {
            $currentCards = $nonSkippedTurns[0]->getCards();
        }

        if ([] === $cards) {
            if ([] === $gameContext->getRound()->getTurns()) {
                throw $this->createRuleException('turn.first.at_least_one_card');
            }

            // skip
            $gameContext->setCurrentCards([]);
            $gameContext->nextPlayer();

            if ($gameContext->getCurrentPlayer()->id === $gameContext->getData('lastPlayer')) {
                $this->handleRoundEnd();
            }

            return;
        }

        match (\count($currentCards)) { // @phpstan-ignore-line
            0 => $this->handleStart($cards),
            1 => $this->handleOneCard($cards, $currentCards),
            2 => $this->handleTwoCards($cards, $currentCards),
            3 => $this->handleThreeCards($cards, $currentCards),
        };

        $hand->removeCards($cards);
        if ($this->isTurnFinished ?? false) {
            return;
        }

        $gameContext->setCurrentCards($cards);
        $gameContext->addData('lastPlayer', $gameContext->getCurrentPlayer()->id);
        $gameContext->nextPlayer();
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

        if (!$this->isLegacyHigher($card, $currentCards[0]) && !$this->isSameRank($card, $currentCards[0])) {
            throw $this->createRuleException('card.value.higher');
        }

        $previousTurns = array_reverse($this->gameContext->getRound()->getTurns());
        $nonSkippedTurns = array_values(array_filter($previousTurns, fn (\App\Model\Turn $turn): bool => !empty($turn->getCards())));

        if ([] === $previousTurns[0]->getCards()) {
            return;
        }

        $lastTurn = $currentCards;
        $beforeLastTurn = ($nonSkippedTurns[1] ?? null)?->getCards() ?? null;

        if ($this->isSameRank($card, $currentCards[0])) {
            $message =
                null !== $beforeLastTurn && $lastTurn[0]->rank === $beforeLastTurn[0]->rank ?
                'Appel aux quatre'
                : \sprintf('%s ou rien', $card->rank->value);
            // maybe use translation here too
            $this->dispatchMercureEvent('message', $message);
        }

        // Rank or nothing :p
        if ($beforeLastTurn && $lastTurn[0]->rank === $beforeLastTurn[0]->rank) {
            // assert skip turn
            if ($card->rank !== $lastTurn[0]->rank) {
                throw $this->createRuleException('card.or_nothing', ['%played_card%' => $card->rank->value, '%actual_card%' => $lastTurn[0]->rank->value]);
            }

            // verify if square
            $rank = $lastTurn[0]->rank;
            $count = array_filter($nonSkippedTurns, fn (\App\Model\Turn $turn): bool => $rank === $turn->getCards()[0]->rank);

            if (3 === count($count)) {
                $this->handleRoundEnd();
            } else {
                $this->gameContext->addData('fastPlay', true);
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
            $this->gameContext->addData('fastPlay', true);

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

        if (!$this->isLegacyHigher($card, $currentCard)) {
            throw $this->createRuleException('card.values.higher');
        }

        $this->gameContext->addData('fastPlay', true);
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

        if (!$this->isLegacyHigher($card, $currentCard)) {
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
        $this->gameContext->setCurrentCards($this->cards);
        $this->gameContext->newRound();
        $this->dispatchMercureEvent('message', 'Fin du tour');
        $this->isTurnFinished = true;
    }
}
