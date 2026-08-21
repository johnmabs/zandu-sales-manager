<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\IdentityAccess\Domain;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\IdentityAccess\Domain\Access\AccessScope;
use Zandu\Modules\IdentityAccess\Domain\Access\Role;
use Zandu\Modules\IdentityAccess\Domain\Access\RoleAssignment;
use Zandu\Modules\IdentityAccess\Domain\Access\RoleCode;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\RoleId;

final class RoleAssignmentTest extends TestCase
{
    public function testItGrantsAnAllowedPermissionWhileActive(): void
    {
        [$assignment, $role] = $this->activeAssignment();

        self::assertTrue($assignment->grants($role, PermissionCode::StoreRead, new DateTimeImmutable('2026-08-21T10:30:00+00:00')));
        self::assertFalse($assignment->grants($role, PermissionCode::StoreUpdate, new DateTimeImmutable('2026-08-21T10:30:00+00:00')));
    }

    public function testExpiredAssignmentAndArchivedRoleGrantNothing(): void
    {
        [$assignment, $role] = $this->activeAssignment();
        self::assertFalse($assignment->grants($role, PermissionCode::StoreRead, new DateTimeImmutable('2026-08-21T11:00:00+00:00')));

        $role->archive();
        self::assertFalse($assignment->grants($role, PermissionCode::StoreRead, new DateTimeImmutable('2026-08-21T10:30:00+00:00')));
    }

    public function testExpiryMustBeAfterAssignment(): void
    {
        $this->expectException(InvalidArgumentException::class);

        RoleAssignment::assign(
            $this->roleId(),
            AccessScope::organization($this->organizationId()),
            $this->actorId(),
            new DateTimeImmutable('2026-08-21T10:00:00+00:00'),
            new DateTimeImmutable('2026-08-21T10:00:00+00:00'),
        );
    }

    /** @return array{RoleAssignment, Role} */
    private function activeAssignment(): array
    {
        $roleId = $this->roleId();
        $role = Role::custom(
            $roleId,
            $this->organizationId(),
            RoleCode::fromString('SUPERVISOR'),
            'Supervisor',
            null,
            [PermissionCode::StoreRead],
        );

        return [
            RoleAssignment::assign(
                $roleId,
                AccessScope::organization($this->organizationId()),
                $this->actorId(),
                new DateTimeImmutable('2026-08-21T10:00:00+00:00'),
                new DateTimeImmutable('2026-08-21T11:00:00+00:00'),
            ),
            $role,
        ];
    }

    private function roleId(): RoleId
    {
        return RoleId::fromString('0198d601-147c-72d5-b75a-a936797ff9c8', new SymfonyUuidFactory());
    }

    private function organizationId(): OrganizationId
    {
        return OrganizationId::fromString('0198d1b1-b2a4-7b6e-8e0e-608484906502', new SymfonyUuidFactory());
    }

    private function actorId(): ActorId
    {
        return ActorId::fromString('0198d601-147c-72d5-b75a-a936797ff9c9', new SymfonyUuidFactory());
    }
}
