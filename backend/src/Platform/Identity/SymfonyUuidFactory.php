<?php

declare(strict_types=1);

namespace Zandu\Platform\Identity;

use InvalidArgumentException;
use Symfony\Component\Uid\Exception\InvalidArgumentException as SymfonyInvalidArgumentException;
use Symfony\Component\Uid\UuidV7;
use Zandu\SharedKernel\Identity\Uuid;
use Zandu\SharedKernel\Identity\UuidFactory;

final class SymfonyUuidFactory implements UuidFactory
{
    public function fromString(string $value): Uuid
    {
        try {
            return new SymfonyUuid(UuidV7::fromString($value));
        } catch (SymfonyInvalidArgumentException $exception) {
            throw new InvalidArgumentException('The value must be a valid UUID v7.', previous: $exception);
        }
    }
}
