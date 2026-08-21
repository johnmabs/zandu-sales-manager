<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\IdentityAccess\Domain;

use PHPUnit\Framework\TestCase;
use Zandu\Modules\IdentityAccess\Domain\Access\RoleCode;
use Zandu\Modules\IdentityAccess\Domain\Access\RoleType;
use Zandu\Modules\IdentityAccess\Domain\Access\SystemRoleCatalog;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Access\PermissionCode;

final class SystemRoleCatalogTest extends TestCase
{
    public function testItProvidesStableMinimalMvpSystemRoles(): void
    {
        $catalog = new SystemRoleCatalog(new SymfonyUuidFactory());
        $roles = $catalog->roles();

        self::assertSame(
            [RoleCode::ORGANIZATION_OWNER, RoleCode::STORE_MANAGER, RoleCode::CASHIER, RoleCode::ACCOUNTANT],
            array_map(static fn($role): string => $role->code()->value(), $roles),
        );
        self::assertSame(
            $catalog->organizationOwnerRoleId()->toString(),
            $roles[0]->id()->toString(),
        );

        foreach ($roles as $role) {
            self::assertSame(RoleType::System, $role->type());
            self::assertNull($role->organizationId());
        }

        self::assertCount(count(PermissionCode::cases()), $roles[0]->permissions());
        self::assertTrue($roles[1]->grants(PermissionCode::StoreUpdate));
        self::assertFalse($roles[1]->grants(PermissionCode::MemberRevoke));
        self::assertSame([PermissionCode::StoreRead], $roles[2]->permissions());
        self::assertTrue($roles[3]->grants(PermissionCode::SecurityAuditRead));
    }
}
