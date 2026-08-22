<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application;

use Zandu\Modules\IdentityAccess\Domain\Invitation\IntendedRoleAssignment;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Identity\UuidFactory;

final readonly class IntendedRoleAssignmentFactory
{
    public function __construct(private UuidFactory $uuidFactory) {}

    /** @param list<string> $storeIds */
    public function create(string $roleCode, array $storeIds): IntendedRoleAssignment
    {
        return IntendedRoleAssignment::forRole(
            $roleCode,
            array_map(fn(string $storeId): StoreId => StoreId::fromString($storeId, $this->uuidFactory), $storeIds),
        );
    }
}
