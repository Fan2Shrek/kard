<?php

namespace App\Domain\Exception;

use App\Service\GameManager\GameMode\GameModeEnum;

class RuleException extends \Exception
{
    private GameModeEnum $gameMode;

    public function __construct(GameModeEnum $gameMode, string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        $this->gameMode = $gameMode;
        parent::__construct($message, $code, $previous);
    }

    public function getGameMode(): GameModeEnum
    {
        return $this->gameMode;
    }
}
