<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Contracts\HttpClient\ResponseInterface;

trait JsonAssertionTrait
{
    public static function assertJsonHasKey(string $key, array|ResponseInterface $response)
    {
        if ($response instanceof ResponseInterface) {
            $response = $response->toArray(false);
        }

        self::assertArrayHasKey($key, $response);
    }

    public static function assertJsonHasNotKey(string $key, array|ResponseInterface $response)
    {
        if ($response instanceof ResponseInterface) {
            $response = $response->toArray(false);
        }

        self::assertArrayNotHasKey($key, $response);
    }
}
