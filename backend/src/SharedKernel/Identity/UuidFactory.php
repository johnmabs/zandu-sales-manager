<?php

declare(strict_types=1);

namespace Zandu\SharedKernel\Identity;

interface UuidFactory
{
    public function fromString(string $value): Uuid;
}
