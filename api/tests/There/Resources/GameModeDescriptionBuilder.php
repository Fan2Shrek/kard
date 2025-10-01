<?php

declare(strict_types=1);

namespace App\Tests\There\Resources;

use App\Entity\GameMode;
use App\Entity\GameModeDescription;

/**
 * @extends AbstractBuilder<GameModeDescription>
 */
final class GameModeDescriptionBuilder extends AbstractBuilder
{
    private string $img = 'https://github.githubassets.com/favicons/favicon.png';
    private string $description = 'POWER';
    private ?GameMode $gameMode = null;

    public function __construct($container)
    {
        parent::__construct($container, GameModeDescription::class);
    }

    public function withImg(string $img): self
    {
        $this->img = $img;

        return $this;
    }

    public function withDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function for(GameMode $gameMode): self
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
        $entity->setImg($this->img);
        $entity->setDescription($this->description);
    }
}
