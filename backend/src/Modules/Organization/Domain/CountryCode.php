<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Domain;

use InvalidArgumentException;

final readonly class CountryCode
{
    private function __construct(private string $value) {}

    public static function fromString(string $value): self
    {
        $normalized = strtoupper($value);

        if (1 !== preg_match('/^[A-Z]{2}$/', $normalized)) {
            throw new InvalidArgumentException('Country code must be an ISO 3166-1 alpha-2 code.');
        }

        return new self($normalized);
    }

    public function value(): string
    {
        return $this->value;
    }
}
