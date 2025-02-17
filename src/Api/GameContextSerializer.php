<?php

namespace App\Api;

use App\Entity\Room;
use App\Model\GameContext;
use App\Model\Player;
use App\Model\Turn;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class GameContextSerializer implements DenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): GameContext
    {
        $players = [];
        foreach ($data['players'] as $player) {
            $players[] = new Player(
                $player['id'],
                $player['username'],
                $player['cardsCount'],
            );
        }

        return new GameContext(
            $data['id'],
            $this->denormalizer->denormalize($data['room'], Room::class, $format, $context),
            $data['assets'],
            $players,
            current(array_filter($players, fn (Player $player) => $player->id === $data['currentPlayer']['id'])),
            $this->denormalizer->denormalize($data['round']['turns'], Turn::class.'[]', $format, $context),
            $data['discarded'],
            $data['data'],
        );
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return GameContext::class === $type;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            GameContext::class => true,
        ];
    }
}
