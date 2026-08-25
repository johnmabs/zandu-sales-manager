<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Domain\ProductPackaging;

use InvalidArgumentException;

final readonly class ProductPackagingName
{
    private function __construct(private string $value) {}

    public static function fromString(string $value): self
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($value));
        if (null === $normalized || '' === $normalized || mb_strlen($normalized) > 160) {
            throw new InvalidArgumentException('Product packaging name must contain between 1 and 160 characters.');
        }

        return new self($normalized);
    }

    public function value(): string
    {
        return $this->value;
    }
}
