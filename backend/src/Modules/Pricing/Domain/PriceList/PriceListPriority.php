<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Domain\PriceList;

use InvalidArgumentException;

final readonly class PriceListPriority
{
    private function __construct(private int $value) {}

    public static function fromInt(int $value): self
    {
        if ($value < 0) {
            throw new InvalidArgumentException('Price list priority must be non-negative.');
        }

        return new self($value);
    }

    public function value(): int
    {
        return $this->value;
    }
}
