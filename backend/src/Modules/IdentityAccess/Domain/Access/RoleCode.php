<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Domain\Access;

use InvalidArgumentException;

final readonly class RoleCode
{
    public const string ORGANIZATION_OWNER = 'ORGANIZATION_OWNER';
    public const string STORE_MANAGER = 'STORE_MANAGER';
    public const string CASHIER = 'CASHIER';
    public const string ACCOUNTANT = 'ACCOUNTANT';

    private function __construct(private string $value) {}
    public static function fromString(string $value): self
    {
        $value = strtoupper(trim($value));
        if (1 !== preg_match('/^[A-Z][A-Z0-9_]{1,63}$/', $value)) {
            throw new InvalidArgumentException('A valid role code is required.');
        }
        return new self($value);
    }

    public static function organizationOwner(): self
    {
        return new self(self::ORGANIZATION_OWNER);
    }
    public function value(): string
    {
        return $this->value;
    }
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function isReservedForSystemRole(): bool
    {
        return in_array($this->value, [self::ORGANIZATION_OWNER, self::STORE_MANAGER, self::CASHIER, self::ACCOUNTANT], true);
    }
}
