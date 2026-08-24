<?php

namespace App\Game\Mode;

use App\Enum\Card\Rank;
use App\Enum\Card\Suit;
use App\Game\Model\Card\Card;
use App\Game\Model\GameContext;
use App\Game\Model\State\GameState;

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

		$currentRound = $context->gameState->getCurrentRound();

		if ($currentRound === null) {
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

		if ($this->isHigherByRankOrder($played, $base) || $base->rank === $played->rank) {
			$context->pushTurn($this->playedCardIds);
		} else {
			throw $this->createRuleException('card.value.higher');
		}

		if (Rank::TWO === $played->rank) {
			$this->handleRoundEnd($context);
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

	private function handleRoundEnd(GameContext $context): void
	{
		$context->endCurrentRound();
		$context->startNewRound();

		$this->shouldPushEndTurn = false;
	}
}
