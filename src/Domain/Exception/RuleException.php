<?php

namespace App\Domain\Exception;

use App\Service\GameManager\GameMode\GameModeEnum;

class RuleException extends \Exception implements TranslatableException
{
    private GameModeEnum $gameMode;

    /**
     * @var array<mixed>
     */
    private array $params = [];

    public function __construct(GameModeEnum $gameMode, string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        $this->gameMode = $gameMode;
        parent::__construct($message, $code, $previous);
    }

    public function getGameMode(): GameModeEnum
    {
        return $this->gameMode;
    }

    public function getTranslationCode(): string
    {
        return $this->getMessage();
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function getDomain(): string
    {
        return $this->getGameMode()->value;
    }

    /**
     * @param array<mixed> $params
     */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function getParams(): array
    {
        return $this->params;
    }
}
