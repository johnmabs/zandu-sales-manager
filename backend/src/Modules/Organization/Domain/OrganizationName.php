<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Domain;

use InvalidArgumentException;

final readonly class OrganizationName
{
    private function __construct(private string $value) {}

    public static function fromString(string $value): self
    {
        $normalized = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
        $length = mb_strlen($normalized);

        if ($length < 2 || $length > 160) {
            throw new InvalidArgumentException('Organization name must contain between 2 and 160 characters.');
        }

        return new self($normalized);
    }

    public function value(): string
    {
        return $this->value;
    }
}
