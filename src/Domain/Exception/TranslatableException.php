<?php

declare(strict_types=1);

namespace App\Domain\Exception;

interface TranslatableException
{
    public function getTranslationCode(): string;

    public function getDomain(): string;

    public function setMessage(string $message): void;

    /**
     * @return array<mixed>
     */
    public function getParams(): array;
}
