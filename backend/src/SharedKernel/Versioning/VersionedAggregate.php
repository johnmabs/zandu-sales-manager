<?php

declare(strict_types=1);

namespace Zandu\SharedKernel\Versioning;

interface VersionedAggregate
{
    public function version(): int;
}
