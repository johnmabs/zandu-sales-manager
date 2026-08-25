<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Domain\Product;

use InvalidArgumentException;

final readonly class ProductName
{
    private function __construct(private string $value) {}

    public static function fromString(string $value): self
    {
        $normalized = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
        $length = mb_strlen($normalized);

        if ($length < 1 || $length > 160) {
            throw new InvalidArgumentException('Product name must contain 1 to 160 characters.');
        }

        return new self($normalized);
    }

    public function value(): string
    {
        return $this->value;
    }
}
