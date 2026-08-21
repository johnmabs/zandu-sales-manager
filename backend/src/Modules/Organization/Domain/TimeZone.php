<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Domain;

use DateTimeZone;
use InvalidArgumentException;

final readonly class TimeZone
{
    private function __construct(private string $value) {}

    public static function fromString(string $value): self
    {
        if (!in_array($value, DateTimeZone::listIdentifiers(), true)) {
            throw new InvalidArgumentException('Time zone must be a valid IANA identifier.');
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
