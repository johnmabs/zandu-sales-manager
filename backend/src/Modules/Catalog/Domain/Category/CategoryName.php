<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Domain\Category;

use InvalidArgumentException;

final readonly class CategoryName
{
    private function __construct(private string $value) {}

    public static function fromString(string $value): self
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($value));

        if (null === $normalized || '' === $normalized || mb_strlen($normalized) > 160) {
            throw new InvalidArgumentException('Category name must contain 1 to 160 characters.');
        }

        return new self($normalized);
    }

    public function value(): string
    {
        return $this->value;
    }
}
