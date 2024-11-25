<?php

namespace Tests;

use App\Service\GameManager\GameMode\GameModeInterface;
use PHPUnit\Framework\TestCase;

abstract class GameModeTestCase extends TestCase
{
    public function getGameMode(): GameModeInterface
    {
        return new $this->getClass();
    }

    abstract protected function getClass(): string;
}
