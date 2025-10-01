<?php

declare(strict_types=1);

namespace App\Api\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\GameModeDescriptionRepository;
use App\Repository\GameModeRepository;

final class GameModeProvider implements ProviderInterface
{
    public function __construct(
        private GameModeRepository $gameModeRepository,
        private GameModeDescriptionRepository $gameModeDescriptionRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $gameModes = [];

        foreach ($this->gameModeRepository->findActiveGameModes() as $gameMode) {
            $description = $this->gameModeDescriptionRepository->findOneBy(['gameMode' => $gameMode]);

            $gameModes[] = [
                'id' => $gameMode->getId(),
                'name' => $gameMode->getValue()?->value,
                'description' => $description ? [
                    'img' => $description->getImg(),
                    'description' => $description->getDescription(),
                ] : null,
            ];
        }

        return $gameModes;
    }
}
