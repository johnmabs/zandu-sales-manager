<?php

declare(strict_types=1);

namespace Zandu\Platform\Identity;

use Symfony\Component\Uid\UuidV7;
use Zandu\SharedKernel\Identity\Uuid;

final readonly class SymfonyUuid implements Uuid
{
    public function __construct(private UuidV7 $uuid)
    {
    }

    public function equals(Uuid $other): bool
    {
        return $this->toString() === $other->toString();
    }

    public function toString(): string
    {
        return $this->uuid->toRfc4122();
    }
}
