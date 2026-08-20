<?php

declare(strict_types=1);

namespace Zandu\SharedKernel\Identity;

interface Uuid
{
    public function equals(self $other): bool;

    public function toString(): string;
}
