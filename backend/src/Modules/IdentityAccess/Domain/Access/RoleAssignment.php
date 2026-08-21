<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Domain\Access;

use DateTimeImmutable;
use InvalidArgumentException;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\RoleId;
use Zandu\SharedKernel\Identity\StoreId;

final readonly class RoleAssignment
{
    private function __construct(
        private RoleId $roleId,
        private AccessScope $scope,
        private ActorId $assignedBy,
        private DateTimeImmutable $assignedAt,
        private ?DateTimeImmutable $expiresAt,
    ) {
        if (null !== $expiresAt && $expiresAt <= $assignedAt) {
            throw new InvalidArgumentException('Role assignment expiry must be after its assignment date.');
        }
    }

    public static function assign(
        RoleId $roleId,
        AccessScope $scope,
        ActorId $assignedBy,
        DateTimeImmutable $assignedAt,
        ?DateTimeImmutable $expiresAt = null,
    ): self {
        return new self($roleId, $scope, $assignedBy, $assignedAt, $expiresAt);
    }

    public function grants(Role $role, PermissionCode $permission, DateTimeImmutable $at, ?StoreId $storeId = null): bool
    {
        if (!$this->roleId->equals($role->id()) || $this->isExpiredAt($at) || !$role->grants($permission)) {
            return false;
        }

        return null === $storeId || $this->scope->includesStore($storeId);
    }

    public function isExpiredAt(DateTimeImmutable $at): bool
    {
        return null !== $this->expiresAt && $at >= $this->expiresAt;
    }

    public function roleId(): RoleId
    {
        return $this->roleId;
    }

    public function scope(): AccessScope
    {
        return $this->scope;
    }

    public function assignedBy(): ActorId
    {
        return $this->assignedBy;
    }

    public function assignedAt(): DateTimeImmutable
    {
        return $this->assignedAt;
    }

    public function expiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }
}
