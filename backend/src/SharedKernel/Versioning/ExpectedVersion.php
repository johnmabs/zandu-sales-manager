<?php

declare(strict_types=1);

namespace Zandu\SharedKernel\Versioning;

use InvalidArgumentException;

final readonly class ExpectedVersion
{
    private function __construct(private int $value) {}

    public static function fromInt(int $value): self
    {
        if ($value < 1) {
            throw new InvalidArgumentException('Expected version must be positive.');
        }

        return new self($value);
    }

    public function assertMatches(VersionedAggregate $aggregate): void
    {
        if ($this->value !== $aggregate->version()) {
            throw AggregateVersionMismatch::between($this->value, $aggregate->version());
        }
    }

    public function toInt(): int
    {
        return $this->value;
    }
}
