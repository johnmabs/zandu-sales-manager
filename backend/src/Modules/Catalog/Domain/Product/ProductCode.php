<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Domain\Product;

use InvalidArgumentException;

final readonly class ProductCode
{
    private function __construct(private string $value) {}

    public static function fromString(string $value): self
    {
        $normalized = mb_strtoupper(trim($value));
        $length = mb_strlen($normalized);

        if ($length < 1 || $length > 64) {
            throw new InvalidArgumentException('Product code must contain 1 to 64 characters.');
        }

        return new self($normalized);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function value(): string
    {
        return $this->value;
    }
}
