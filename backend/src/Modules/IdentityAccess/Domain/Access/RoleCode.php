<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Domain\Access;

use InvalidArgumentException;

final readonly class RoleCode
{
    private function __construct(private string $value) {}
    public static function fromString(string $value): self
    {
        $value = strtoupper(trim($value));
        if (1 !== preg_match('/^[A-Z][A-Z0-9_]{1,63}$/', $value)) {
            throw new InvalidArgumentException('A valid role code is required.');
        }
        return new self($value);
    }
    public function value(): string
    {
        return $this->value;
    }
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
