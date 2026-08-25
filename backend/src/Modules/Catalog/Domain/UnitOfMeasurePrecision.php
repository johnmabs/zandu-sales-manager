<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Domain;

use InvalidArgumentException;

final readonly class UnitOfMeasurePrecision
{
    private function __construct(private int $value) {}

    public static function fromInt(int $value): self
    {
        if ($value < 0 || $value > 12) {
            throw new InvalidArgumentException('Unit of measure precision must be between 0 and 12.');
        }

        return new self($value);
    }

    public function value(): int
    {
        return $this->value;
    }
}
