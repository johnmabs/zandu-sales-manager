<?php

declare(strict_types=1);

namespace Zandu\SharedKernel\Versioning;

use InvalidArgumentException;

trait TracksAggregateVersion
{
    private int $version;

    final public function version(): int
    {
        $this->assertValidVersion();

        return $this->version;
    }

    final protected function advanceVersion(): void
    {
        $this->assertValidVersion();
        ++$this->version;
    }

    final protected function assertValidVersion(): void
    {
        if ($this->version < 1) {
            throw new InvalidArgumentException('Aggregate version must be positive.');
        }
    }
}
