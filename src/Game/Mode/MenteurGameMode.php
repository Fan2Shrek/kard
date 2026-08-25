<?php

namespace App\Game\Mode;

use App\Enum\Card\Rank;
use App\Game\Model\GameContext;
use App\Game\Model\State\GameState;
use App\Game\Model\State\PlayerState;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @see https://fr.wikipedia.org/wiki/Menteur_(jeu_de_cartes)
 */
final class MenteurGameMode extends AbstractGameMode implements SetupGameModeInterface
{
    use CardsHelperTrait;

    public function getGameMode(): GameModeEnum
    {
        return GameModeEnum::MENTEUR;
    }

    public function getCardsCount(int $playerCount): ?int
    {
        return null;
    }

    public function getPlayerOrder(GameState $state): array
    {
        // array_keys() would do here, but PHP silently casts numeric-looking
        // string keys to int - reading ->id directly keeps these real strings
        $ids = array_map(fn (PlayerState $player): string => $player->id, array_values($state->players));
        shuffle($ids);

        return $ids;
    }

    public function setup(GameContext $ctx): void
    {
        $ctx->startNewRound();
    }

    public function isGameFinished(GameState $state): bool
    {
        foreach ($state->players as $player) {
            if (0 === $player->hand->count()) {
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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->remove('withJokers');
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

    protected function doPlay(array $cards, GameContext $context, array $data): void
    {
    }
}
