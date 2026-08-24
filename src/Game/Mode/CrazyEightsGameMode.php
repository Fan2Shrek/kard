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
		if (empty($cards)) {
			$context->drawCard($context->gameState->currentPlayerId);

			return;
		}

		if (!$this->allSameRank($cards)) {
			throw $this->createRuleException('cards.same_rank');
		}

		$round = $context->gameState->getCurrentRound();

		$lastTurnCards = $round->getLastTurn()->cardIds ?? [];
		$lastCard = \end($lastTurnCards);
		$lastCard = $context->gameState->getCardById($lastCard);

		$activeSuit = $this->getActiveSuit($round, $lastCard);
		$mainCard = $cards[0];

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
			count($context->gameState->players) === 2 ? $context->skipNextPlayerTurn() : $context->reversePlayerOrder();
		}

		if (Rank::TWO === $mainCard->rank) {
			$nextPlayerId = $context->gameState->getNextPlayerId();

			for ($i = 0; $i < 2 * count($this->playedCardIds); ++$i) {
				$context->drawCard($nextPlayerId);
			}

			$context->skipNextPlayerTurn();
		}

		$context->pushTurn($this->playedCardIds);
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
