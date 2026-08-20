<?php

declare(strict_types=1);

namespace Zandu\SharedKernel\Money;

use InvalidArgumentException;

final readonly class Currency
{
    private function __construct(private string $code)
    {
    }

    public static function fromCode(string $code): self
    {
        $normalizedCode = strtoupper($code);

        if (1 !== preg_match('/^[A-Z]{3}$/', $normalizedCode)) {
            throw new InvalidArgumentException('Currency code must contain exactly three ASCII letters.');
        }

        return new self($normalizedCode);
    }

    public function equals(self $other): bool
    {
        return $this->code === $other->code;
    }

    public function code(): string
    {
        return $this->code;
    }
}
