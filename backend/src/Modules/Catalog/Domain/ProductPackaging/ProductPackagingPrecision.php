<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Domain\ProductPackaging;

use InvalidArgumentException;

final readonly class ProductPackagingPrecision
{
    private function __construct(private int $value) {}

    public static function fromInt(int $value): self
    {
        if ($value < 0 || $value > 12) {
            throw new InvalidArgumentException('Product packaging precision must be between 0 and 12.');
        }

        return new self($value);
    }

    public function value(): int
    {
        return $this->value;
    }
}
