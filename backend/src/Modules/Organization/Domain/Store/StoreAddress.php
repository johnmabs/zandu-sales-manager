<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Domain\Store;

use InvalidArgumentException;

final readonly class StoreAddress
{
    private function __construct(private string $value) {}

    public static function fromString(string $value): self
    {
        $normalized = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);

        if ('' === $normalized || mb_strlen($normalized) > 500) {
            throw new InvalidArgumentException('Store address must contain between 1 and 500 characters.');
        }

        return new self($normalized);
    }

    public function value(): string
    {
        return $this->value;
    }
}
