<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Domain;

use InvalidArgumentException;

final readonly class UnitOfMeasureName
{
    private function __construct(private string $value) {}

    public static function fromString(string $value): self
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($value));

        if (null === $normalized || '' === $normalized || mb_strlen($normalized) > 100) {
            throw new InvalidArgumentException('Unit of measure name must contain 1 to 100 characters.');
        }

        return new self($normalized);
    }

    public function value(): string
    {
        return $this->value;
    }
}
