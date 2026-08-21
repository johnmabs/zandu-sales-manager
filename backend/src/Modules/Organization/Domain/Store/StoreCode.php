<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Domain\Store;

use InvalidArgumentException;

final readonly class StoreCode
{
    private function __construct(private string $value) {}

    public static function fromString(string $value): self
    {
        $normalized = strtoupper(trim($value));

        if (1 !== preg_match('/^[A-Z0-9][A-Z0-9_-]{1,31}$/', $normalized)) {
            throw new InvalidArgumentException('Store code must contain 2 to 32 uppercase letters, digits, dashes or underscores.');
        }

        return new self($normalized);
    }

    public function value(): string
    {
        return $this->value;
    }
}
