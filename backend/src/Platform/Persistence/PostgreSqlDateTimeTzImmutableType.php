<?php

declare(strict_types=1);

namespace Zandu\Platform\Persistence;

use DateTimeImmutable;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DateTimeTzImmutableType;
use Doctrine\DBAL\Types\Exception\InvalidFormat;

final class PostgreSqlDateTimeTzImmutableType extends DateTimeTzImmutableType
{
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?DateTimeImmutable
    {
        try {
            return parent::convertToPHPValue($value, $platform);
        } catch (InvalidFormat $exception) {
            if (!is_string($value)) {
                throw $exception;
            }

            $converted = DateTimeImmutable::createFromFormat('Y-m-d H:i:s.uP', $value)
                ?: DateTimeImmutable::createFromFormat('Y-m-d H:i:sP', $value);

            if (false === $converted) {
                throw $exception;
            }

            return $converted;
        }
    }
}
