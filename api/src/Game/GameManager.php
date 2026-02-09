<?php

declare(strict_types=1);

namespace App\Game;

use App\Entity\Room;
use App\Entity\User;
use App\Enum\GameStatusEnum;
use App\Game\State\GameEvent;
use App\Game\State\GameState;
use App\Game\State\PlayerState;
use App\Service\Card\CardGenerator;
use App\Service\Game\GameModeProvider;
use App\Service\Game\State\GameStateRepositoryInterface;

final class GameManager
{
	public function __construct(
		private CardGenerator $cardGenerator,
		private GameModeProvider $gameModeProvider,
		private GameEventApplier $gameEventApplier,
	) {
	}

	public function startRoom(Room $room): GameState
	{
        $room->setStatus(GameStatusEnum::PLAYING);

		$deck = $this->cardGenerator->generateShuffled();
		$gameMode = $this->gameModeProvider->getForRoom($room);

		$players = array_map(
			fn(User $user) => new PlayerState(Player::fromUser($user), []),
			$room->getParticipants()->toArray()
		);

		$gameState = new GameState(
			$players,
			0,
			null,
			[],
			$deck->getCards(),
		);

		$cardsPerPlayer = $gameMode->getCardsCount(count($players));
		$events = [];
		for ($i = 0; $i < ($cardsPerPlayer ?? intdiv($deck->count(), count($players))); ++$i) {
			foreach ($players as $state) {
				$events[] = GameEvent::game(
					GameEvent::CARD_DRAW,
					[
						'playerId' => $state->player->id,
					],
				);
			}
		}

		return $this->gameEventApplier->applyMultiple($events, $gameState);
	}

    /**
     * @param array<Card>          $cards
     * @param array<string, mixed> $data
     */
	public function play(Room $room, GameState $state, Player $player, array $cards, array $data = []): void
	{
		if ($state->currentPlayerId !== $player->id) {
			throw new \LogicException("It's not this player's turn");
		}

		$hand = $state->getPlayerStateById($player->id)->hand;
		if (!empty($cards) && count(array_diff($cards, $hand)) > 0) {
			throw new \LogicException("Player doesn't have these cards in hand");
		}

		$gameEvent = GameEvent::game(
			GameEvent::CARD_PLAYED,
			[
				'playerId' => $player->id,
				'cards' => $cards,
				'data' => $data,
				'gameMode' => $room->getGameMode()->getValue(),
			],
		);

		$newState = $this->gameEventApplier->apply($gameEvent, $state);
	}
}
