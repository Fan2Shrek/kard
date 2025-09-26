<?php

namespace App\Tests\Resource;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;
use Symfony\Component\Mercure\Jwt\TokenProviderInterface;
use Symfony\Component\Mercure\Update;

final class HubSpy implements HubInterface
{
    public static $published = [];

    public function publish(Update $update): string
    {
        static::$published[] = $update;

        return 'id';
    }

    public function getUrl(): string
    {
        return 'http://example.com/hub';
    }

    public function getPublicUrl(): string
    {
        return 'http://example.com/hub';
    }

    public function getProvider(): TokenProviderInterface
    {
        return new class implements TokenProviderInterface {
            public function createToken(string $topic): string
            {
                return 'token';
            }
        };
    }

    public function getFactory(): ?TokenFactoryInterface
    {
        return null;
    }

    public static function reset(): void
    {
        static::$published = [];
    }
}
