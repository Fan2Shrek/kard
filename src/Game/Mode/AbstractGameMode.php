<?php

declare(strict_types=1);

namespace App\Game\Mode;

use App\Game\Exception\RuleException;
use App\Game\Model\Card\Card;
use App\Game\Model\GameContext;
use App\Game\Model\State\GameState;
use Override;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractGameMode implements GameModeInterface
{
    protected GameState $gameState;

	protected bool $shouldPushEndTurn = true;

    /**
     * @var string[]
     */
    protected array $playedCardIds;

    public function play(array $cards, GameContext $context, string $playerId, array $data = []): void
    {
        $this->playedCardIds = $cards;
        $this->gameState = $context->gameState;
        // game modes are shared, long-lived services - reset per-play state here
        // rather than relying on every round-ending branch to restore it itself
        $this->shouldPushEndTurn = true;

		$this->validatePlay($cards, $context, $playerId, $data);

		$cards = array_map(fn (string $c) => $context->gameState->getCardById($c), $cards);

        $this->doPlay($cards, $context, $data);

		$this->postPlay($context, $playerId, $data);
    }

	public function configureOptions(OptionsResolver $resolver): void
	{
	}

    /**
     * This method implements the game rules.
     *
     * @param array<Card>          $cards
     * @param array<string, mixed> $data
     */
    abstract protected function doPlay(array $cards, GameContext $context, array $data): void;

    /**
     * @param array<mixed> $params
     */
    protected function createRuleException(string $message, array $params = []): RuleException
    {
        $e = new RuleException($this->getGameMode(), $message);
        $e->setParams($params);

        return $e;
    }

	/**
	 * @param string[]              $cards
	 * @param array<string, mixed>  $data
	 */
	protected function validatePlay(array $cards, GameContext $context, string $playerId, array $data = []): void
	{
		$hand = $context->gameState->getPlayerStateById($playerId)->hand;

		if (!$hand->hasCards($cards)) {
			throw $this->createRuleException('Card not in your hand');
		}
	}

	/**
	 * @param array<string, mixed> $data
	 */
	protected function postPlay(GameContext $context, string $playerId, array $data = []): void
	{
		foreach ($this->playedCardIds as $cardId) {
			$context->pushCardDiscarded($cardId, $playerId);
		}

		if ($this->shouldPushEndTurn) {
			$context->pushEndTurn();
		}
	}
}
