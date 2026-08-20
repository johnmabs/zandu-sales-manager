<?php

declare(strict_types=1);

namespace Zandu\Platform\Identity;

use Symfony\Component\Uid\UuidV7;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\Uuid;

final class SymfonyUuidV7Generator implements IdGenerator
{
    public function generate(): Uuid
    {
        return new SymfonyUuid(new UuidV7());
    }
}
