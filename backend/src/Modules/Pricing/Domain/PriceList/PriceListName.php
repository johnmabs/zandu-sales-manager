<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Domain\PriceList;

use InvalidArgumentException;

final readonly class PriceListName
{
    private function __construct(private string $value) {}

    public static function fromString(string $value): self
    {
        $normalized = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
        $length = mb_strlen($normalized);

        if ($length < 1 || $length > 160) {
            throw new InvalidArgumentException('Price list name must contain 1 to 160 characters.');
        }

        return new self($normalized);
    }

    public function value(): string
    {
        return $this->value;
    }
}
