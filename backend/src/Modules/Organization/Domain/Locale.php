<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Domain;

use InvalidArgumentException;

final readonly class Locale
{
    private function __construct(private string $value) {}

    public static function fromString(string $value): self
    {
        if (1 !== preg_match('/^[a-z]{2}(?:_[A-Z]{2})?$/', $value)) {
            throw new InvalidArgumentException('Locale must use the ll or ll_CC format.');
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
