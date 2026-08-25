<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Domain\ProductPackaging;

use InvalidArgumentException;

final readonly class ProductPackagingCode
{
    private function __construct(private string $value) {}

    public static function fromString(string $value): self
    {
        $normalized = mb_strtoupper(trim($value), 'UTF-8');
        if ('' === $normalized || mb_strlen($normalized) > 64) {
            throw new InvalidArgumentException('Product packaging code must contain between 1 and 64 characters.');
        }

        return new self($normalized);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
