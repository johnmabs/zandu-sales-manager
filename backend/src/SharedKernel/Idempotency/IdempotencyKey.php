<?php

declare(strict_types=1);

namespace Zandu\SharedKernel\Idempotency;

use InvalidArgumentException;

final readonly class IdempotencyKey
{
    public const MAX_LENGTH = 255;

    private function __construct(private string $value) {}

    public static function fromString(string $value): self
    {
        if ('' === trim($value)) {
            throw new InvalidArgumentException('Idempotency key must not be empty.');
        }

        if (self::MAX_LENGTH < strlen($value)) {
            throw new InvalidArgumentException(sprintf(
                'Idempotency key must not exceed %d bytes.',
                self::MAX_LENGTH,
            ));
        }

        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function toString(): string
    {
        return $this->value;
    }
}
