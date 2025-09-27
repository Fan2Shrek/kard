<?php

declare(strict_types=1);

namespace App\Tests\There;

final class ThereIs
{
    private static $container;

    public static function setContainer($container): void
    {
        self::$container = $container;
    }

    public static function aGameMode(): Resources\GameModeBuilder
    {
        return new Resources\GameModeBuilder(self::$container);
    }

    public static function aUser(): Resources\UserBuilder
    {
        return new Resources\UserBuilder(self::$container);
    }

    public static function aRoom(): Resources\RoomBuilder
    {
        return new Resources\RoomBuilder(self::$container);
    }
}
