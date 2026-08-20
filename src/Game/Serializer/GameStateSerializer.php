<?php

namespace App\Game\Serializer;

use App\Entity\Room;
use App\Game\Model\Card\Card;
use App\Game\Model\GameState;
use App\Game\Model\Player;
use App\Game\Model\Turn;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class GameStateSerializer implements DenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): GameState
    {
        $players = [];
        foreach ($data['players'] as $player) {
            $players[] = new Player(
                $player['id'],
                $player['username'],
                $player['cardsCount'],
            );
        }

        return new GameState(
            $data['id'],
            $this->denormalizer->denormalize($data['room'], Room::class, $format, $context),
            $players,
            current(array_filter($players, fn (Player $player): bool => $player->id === $data['currentPlayer']['id'])),
            $this->denormalizer->denormalize($data['round']['turns'], Turn::class.'[]', $format, $context),
            array_map(
                fn ($card): mixed => $this->denormalizer->denormalize($card, Card::class, $format, $context),
                $data['drawPile'],
            ),
            $data['discarded'],
            $data['data'],
        );
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return GameState::class === $type;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            GameState::class => true,
        ];
    }
}
