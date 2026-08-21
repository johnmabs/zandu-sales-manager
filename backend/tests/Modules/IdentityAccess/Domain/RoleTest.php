<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\IdentityAccess\Domain;

use LogicException;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\IdentityAccess\Domain\Access\PermissionCode;
use Zandu\Modules\IdentityAccess\Domain\Access\Role;
use Zandu\Modules\IdentityAccess\Domain\Access\RoleCode;
use Zandu\Modules\IdentityAccess\Domain\Access\RoleStatus;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\RoleId;

final class RoleTest extends TestCase
{
    public function testSystemRoleIsGlobalImmutableAndTyped(): void
    {
        $role = Role::system($this->roleId(), RoleCode::fromString('organization_owner'), 'Owner', null, [PermissionCode::OrganizationRead]);
        self::assertNull($role->organizationId());
        self::assertTrue($role->grants(PermissionCode::OrganizationRead));
        $this->expectException(LogicException::class);
        $role->archive();
    }

    public function testArchivedCustomRoleGrantsNoPermission(): void
    {
        $role = Role::custom(
            $this->roleId(),
            OrganizationId::fromString('0198d1b1-b2a4-7b6e-8e0e-608484906502', new SymfonyUuidFactory()),
            RoleCode::fromString('SUPERVISOR'),
            'Supervisor',
            null,
            [PermissionCode::StoreRead, PermissionCode::StoreRead, PermissionCode::StoreUpdate],
        );
        self::assertCount(2, $role->permissions());
        $role->archive();
        self::assertSame(RoleStatus::Archived, $role->status());
        self::assertFalse($role->grants(PermissionCode::StoreRead));
        self::assertSame(2, $role->version());
    }

    private function roleId(): RoleId
    {
        return RoleId::fromString('0198d601-147c-72d5-b75a-a936797ff9c8', new SymfonyUuidFactory());
    }
}
