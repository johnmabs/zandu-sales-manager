<?php

declare(strict_types=1);

namespace Zandu\SharedKernel\Versioning;

use RuntimeException;
use Zandu\SharedKernel\Error\ResourceConflict;

final class AggregateVersionMismatch extends RuntimeException implements ResourceConflict
{
    public static function between(int $expected, int $actual): self
    {
        return new self(sprintf('Expected aggregate version %d, got %d.', $expected, $actual));
    }
}
