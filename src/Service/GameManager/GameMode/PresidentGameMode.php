<?php

namespace App\Service\GameManager\GameMode;

use App\Domain\Exception\RuleException;
use App\Enum\Card\Rank;
use App\Enum\Card\Suit;
use App\Model\Card\Card;
use App\Model\GameContext;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

/**
 * @see https://bicyclecards.com/how-to-play/presidents
 *
 * @todo gameData in gameContext ??
 */
final class PresidentGameMode implements GameModeInterface
{
    use CardsHelperTrait;

    private GameContext $gameContext;
    private bool $isTurnFinished;

    public function __construct(
        private HubInterface $hub,
    ) {}

    public function getGameMode(): GameModeEnum
    {
        return GameModeEnum::PRESIDENT;
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

    public function play(array $cards, GameContext $gameContext): void
    {
        $this->gameContext = $gameContext;
        $currentCards = $gameContext->getCurrentCards();

        if (count($cards) > 3) { // TODO handle revolution
            throw new RuleException($this->getGameMode(), 'Invalid number of cards played');
        }

        match (\count($currentCards)) {
            0 => $this->handleStart($cards),
            1 => $this->handleOneCard($cards, $currentCards),
            2 => $this->handleTwoCards($cards, $currentCards),
            3 => $this->handleThreeCards($cards, $currentCards),
        };

        if ($this->isTurnFinished ?? false) {
            return;
        }

        $gameContext->setCurrentCards($cards);
        $gameContext->nextPlayer();
    }

    private function handleOneCard(array $cards, array $currentCard): void
    {
        if (count($cards) !== 1) {
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

        [$lastTurn, $beforeLastTurn] = [$previousTurns[0]->getCards() ?? null, ($previousTurns[1] ?? null)?->getCards() ?? null];

        if (null === $beforeLastTurn || null === $lastTurn) {
            if ($this->isSameRank($card, $currentCard[0])) {
                $this->dispatchMercureEvent('message', \sprintf("%s ou rien", $card->rank->value));;
            }

            return;
        }

        // Rank or nothing :p
        if ($lastTurn[0]->rank === $beforeLastTurn[0]->rank) {
            if ($card->rank !== $lastTurn[0]->rank) {
                throw new RuleException($this->getGameMode(), \sprintf('Can not play "%s" when "%s" or nothing.', $card->rank->value, $lastTurn[0]->rank->value));
            }
        }
    }

    private function handleStart(array $cards): void
    {
        if (!$this->allSameRank($cards)) {
            throw new RuleException($this->getGameMode(), "Can't play multiple cards with different values");
        }
    }

    private function handleTwoCards(array $cards, array $currentCards): void
    {
        if (count($cards) !== 2) {
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
            throw new RuleException($this->getGameMode(), 'A card with a higher or same value must be played');
        }
    }

    private function handleThreeCards(array $cards, array $currentCards): void
    {
        // TODO
    }

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
        $this->gameContext->newRound();
        $this->dispatchMercureEvent('message', 'Fin du tour');
        $this->isTurnFinished = true;
    }

    private function dispatchMercureEvent(string $eventName, string $text): void
    {
        $this->hub->publish(new Update(
            \sprintf('room-%s', $this->gameContext->getId()),
            \json_encode([
                'action' => $eventName,
                'data' => [
                    'text' => $text,
                ],
            ])
        ));
    }
}
