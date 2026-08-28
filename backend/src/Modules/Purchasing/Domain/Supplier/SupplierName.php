<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Domain\Supplier;

use InvalidArgumentException;

final readonly class SupplierName
{
    private function __construct(private string $value) {}

    public static function fromString(string $value): self
    {
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? '';
        if ('' === $value) {
            throw new InvalidArgumentException('Supplier name cannot be empty.');
        }
        if (mb_strlen($value) > 160) {
            throw new InvalidArgumentException('Supplier name cannot exceed 160 characters.');
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
