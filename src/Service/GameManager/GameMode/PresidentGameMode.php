<?php

namespace App\Service\GameManager\GameMode;

use App\Domain\Exception\RuleException;
use App\Enum\Card\Rank;
use App\Enum\Card\Suit;
use App\Model\Card\Card;
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

    protected function doPlay(array $cards, GameContext $gameContext): void
    {
        $this->cards = $cards;
        $this->gameContext = $gameContext;
        $currentCards = $gameContext->getCurrentCards();

        if (count($cards) > 3) { // TODO handle revolution
            throw new RuleException($this->getGameMode(), 'Invalid number of cards played');
        }

        if (0 === count($cards)) {
            if (0 === count($gameContext->getRound()->getTurns())) {
                throw new RuleException($this->getGameMode(), 'First turn must have at least one card');
            }

            // skip
            $gameContext->setCurrentCards([]);
            $gameContext->nextPlayer();

            if ($gameContext->getCurrentPlayer()->id === $gameContext->getData('lastPlayer')) {
                $this->handleRoundEnd();
            }

            return;
        }

        match (\count($currentCards)) {
            0 => $this->handleStart($cards),
            1 => $this->handleOneCard($cards, $currentCards),
            2 => $this->handleTwoCards($cards, $currentCards),
            3 => $this->handleThreeCards($cards, $currentCards),
            default => throw new \LogicException('Invalid number of cards played'),
        };

        if ($this->isTurnFinished ?? false) {
            return;
        }

        $gameContext->setCurrentCards($cards);
        $gameContext->addData('lastPlayer', $gameContext->getCurrentPlayer()->id);
        $gameContext->nextPlayer();
    }

    /**
     * @param Card[] $cards
     * @param Card[] $currentCard
     */
    private function handleOneCard(array $cards, array $currentCard): void
    {
        if (1 !== count($cards)) {
            throw new RuleException($this->getGameMode(), 'Incorrect number of cards played');
        }

        $previousTurns = array_reverse($this->gameContext->getRound()->getTurns());

        $card = $cards[0];

        if (!$this->isLegacyHigher($card, $currentCard[0]) && !$this->isSameRank($card, $currentCard[0])) {
            throw new RuleException($this->getGameMode(), 'A card with a higher or same value must be played');
        }

        if (Rank::TWO === $card->rank) {
            $this->handleRoundEnd();
        }

        $nonSkippedTurns = array_values(array_filter($previousTurns, fn ($turn) => !empty($turn->getCards())));

        [$lastTurn, $beforeLastTurn] = [$nonSkippedTurns[0]->getCards() ?? null, ($nonSkippedTurns[1] ?? null)?->getCards() ?? null]; // @phpstan-ignore-line

        if ($this->isSameRank($card, $currentCard[0])) {
            $this->dispatchMercureEvent('message', \sprintf('%s ou rien', $card->rank->value));
        }

        if (null === $beforeLastTurn) {
            return;
        }

        // Rank or nothing :p
        if ($lastTurn[0]->rank === $beforeLastTurn[0]->rank) {
            // assert skip turn
            if ($card->rank !== $lastTurn[0]->rank) {
                throw new RuleException($this->getGameMode(), \sprintf('Can not play "%s" when "%s" or nothing.', $card->rank->value, $lastTurn[0]->rank->value));
            }

            // verify if square
            $rank = $lastTurn[0]->rank;
            $count = array_filter($nonSkippedTurns, fn ($turn) => $rank === $turn->getCards()[0]->rank);

            if (3 === count($count)) {
                $this->handleRoundEnd();
            }
        }
    }

    /**
     * @param Card[] $cards
     */
    private function handleStart(array $cards): void
    {
        if (!$this->allSameRank($cards)) {
            throw new RuleException($this->getGameMode(), "Can't play multiple cards with different values");
        }

        [$card] = $cards;

        if (Rank::TWO === $card->rank) {
            $this->handleRoundEnd();
        }
    }

    /**
     * @param Card[] $cards
     * @param Card[] $currentCards
     */
    private function handleTwoCards(array $cards, array $currentCards): void
    {
        if (2 !== count($cards)) {
            throw new RuleException($this->getGameMode(), 'Incorrect number of cards played');
        }

        if (!$this->allSameRank($cards)) {
            throw new RuleException($this->getGameMode(), "Can't play multiple cards with different values");
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
            throw new RuleException($this->getGameMode(), 'Cards with a higher or same value must be played');
        }
    }

    /**
     * @param Card[] $cards
     * @param Card[] $currentCards
     */
    private function handleThreeCards(array $cards, array $currentCards): void
    {
        if (3 !== count($cards)) {
            throw new RuleException($this->getGameMode(), 'Incorrect number of cards played');
        }

        if (!$this->allSameRank($cards)) {
            throw new RuleException($this->getGameMode(), "Can't play multiple cards with different values");
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
            throw new RuleException($this->getGameMode(), 'Cards with a higher or same value must be played');
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
