<?php

declare(strict_types=1);

namespace App\Doctrine;

use App\Game\Model\GameConfiguration;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class RoomConfigurationType extends Type
{
    public const NAME = 'room_configuration';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getJsonTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof GameConfiguration) {
            throw new \InvalidArgumentException('Expected PlayerState.');
        }

        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?GameConfiguration
    {
        if ($value === null || $value instanceof GameConfiguration) {
            return $value;
        }

        /** @var array<string, mixed> $data */
        $data = json_decode($value, true, 512, JSON_THROW_ON_ERROR);

		return GameConfiguration::fromArray($data);
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
