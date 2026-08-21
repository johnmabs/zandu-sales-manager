<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Domain\Invitation;

use InvalidArgumentException;
use Zandu\Modules\IdentityAccess\Domain\Access\RoleCode;
use Zandu\SharedKernel\Identity\StoreId;

final readonly class IntendedRoleAssignment
{
    /** @param list<StoreId> $storeIds */
    private function __construct(private string $roleCode, private array $storeIds) {}

    /** @param list<StoreId> $storeIds */
    public static function forRole(string $roleCode, array $storeIds = []): self
    {
        $roleCode = strtoupper(trim($roleCode));
        if (1 !== preg_match('/^[A-Z][A-Z0-9_]{1,63}$/', $roleCode)) {
            throw new InvalidArgumentException('A valid intended role code is required.');
        }

        $uniqueStores = [];
        foreach ($storeIds as $storeId) {
            $uniqueStores[$storeId->toString()] = $storeId;
        }

        return new self($roleCode, array_values($uniqueStores));
    }

    public function roleCode(): string
    {
        return $this->roleCode;
    }

    public function matches(RoleCode $roleCode): bool
    {
        return $this->roleCode === $roleCode->value();
    }

    /** @return list<StoreId> */
    public function storeIds(): array
    {
        return $this->storeIds;
    }
}
