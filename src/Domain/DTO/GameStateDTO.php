<?php

declare(strict_types=1);

namespace App\Domain\DTO;

use App\Game\Model\Card\Card;
use App\Game\Model\State\GameState;
use App\Game\Model\State\PlayerState;
use App\Game\Model\State\Round;
use App\Game\Model\State\Turn;

/**
 * What the front actually needs to render a game: players (with hands
 * redacted for everyone except the requesting viewer), rounds/turns and the
 * discard pile with card ids resolved to real Card data, and the draw pile
 * as a plain count (never expose its order/contents - that's the deck).
 */
final readonly class GameStateDTO
{
    /**
     * @param PlayerStateDTO[]               $players
     * @param string[]                       $playerOrder
     * @param TurnDTO[][]                    $rounds
     * @param Card[]                         $discardPile
     * @param array<string, list<Card|null>> $piles       named stacks, layout modes only (solitaire); null is a face-down card
     */
    public function __construct(
        public array $players,
        public array $playerOrder,
        public string $currentPlayerId,
        public array $rounds,
        public array $discardPile,
        public int $drawPileCount,
        public bool $everyoneCanPlay,
        public array $piles = [],
    ) {
    }

    public static function fromState(GameState $state, ?string $viewerId = null): self
    {
        $players = array_map(
            fn (PlayerState $player): PlayerStateDTO => PlayerStateDTO::fromPlayerState(
                $player,
                $state,
                $player->id === $viewerId,
            ),
            array_values($state->players),
        );

        $rounds = array_values(array_map(
            fn (Round $round): array => array_values(array_map(
                fn (Turn $turn): TurnDTO => TurnDTO::fromTurn($turn, $state),
                $round->turns,
            )),
            $state->rounds,
        ));

        $discardPile = array_values(array_map(
            fn (string $cardId): Card => $state->cards[$cardId],
            $state->discardPile->cards,
        ));

        // face-down halves stay counted, never enumerated - same reason the draw
        // pile is exposed as a count only
        $piles = [];

        foreach ($state->piles as $key => $pile) {
            $piles[$key] = str_ends_with($key, '_down')
                ? array_fill(0, $pile->count(), null)
                : array_values(array_map(fn (string $cardId): Card => $state->cards[$cardId], $pile->cards));
        }

        return new self(
            $players,
            $state->playerOrder,
            $state->currentPlayerId,
            $rounds,
            $discardPile,
            $state->drawPile->count(),
            $state->everyoneCanPlay(),
            $piles,
        );
    }
}

/**
 * @internal
 */
final readonly class PlayerStateDTO
{
    /**
     * @param Card[]|null $hand null unless this is the requesting viewer's own player
     */
    public function __construct(
        public string $id,
        public string $username,
        public int $score,
        public int $cardsCount,
        public ?array $hand,
    ) {
    }

    public static function fromPlayerState(PlayerState $player, GameState $state, bool $isViewer): self
    {
        return new self(
            $player->id,
            $player->playerName,
            $player->score,
            $player->hand->count(),
            $isViewer ? array_values(array_map(fn (string $cardId): Card => $state->cards[$cardId], $player->hand->cards)) : null,
        );
    }
}

/**
 * @internal
 */
final readonly class TurnDTO
{
    /**
     * @param Card[]               $cards empty means this turn was a pass
     * @param array<string, mixed> $data  game-mode-specific extras (e.g. Menteur's declared rank, Crazy Eights' chosen suit)
     */
    public function __construct(
        public string $playerId,
        public array $cards,
        public array $data,
    ) {
    }

    public static function fromTurn(Turn $turn, GameState $state): self
    {
        return new self(
            $turn->playerId,
            array_values(array_map(fn (string $cardId): Card => $state->cards[$cardId], $turn->cardIds)),
            $turn->data,
        );
    }
}
