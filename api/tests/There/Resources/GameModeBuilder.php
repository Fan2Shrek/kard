<?php

declare(strict_types=1);

namespace App\Tests\There\Resources;

use App\Entity\GameMode;
use App\Service\GameManager\GameMode\GameModeEnum;

/**
 * @extends AbstractBuilder<GameMode>
 */
final class GameModeBuilder extends AbstractBuilder
{
    private bool $isActive = true;
    private GameModeEnum $gameMode = GameModeEnum::PRESIDENT;

    public function __construct($container)
    {
        parent::__construct($container, GameMode::class);
    }

    public function inactive(): self
    {
        $this->isActive = false;

        return $this;
    }

    public function for(GameModeEnum $gameMode): self
    {
        $this->gameMode = $gameMode;

        return $this;
    }

    protected function getParams(): array
    {
        return [
            $this->gameMode,
        ];
    }

    protected function afterBuild(object $entity): void
    {
        $entity->setActive($this->isActive);
    }
}
