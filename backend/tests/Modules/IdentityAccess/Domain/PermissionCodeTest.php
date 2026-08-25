<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\IdentityAccess\Domain;

use PHPUnit\Framework\TestCase;
use Zandu\SharedKernel\Access\PermissionCode;

final class PermissionCodeTest extends TestCase
{
    public function testCatalogContainsOnlyPermissionsUsedByImplementedUseCases(): void
    {
        self::assertSame([
            'ORGANIZATION_READ', 'ORGANIZATION_UPDATE', 'ORGANIZATION_SUSPEND',
            'STORE_CREATE', 'STORE_READ', 'STORE_UPDATE', 'STORE_SUSPEND', 'STORE_CLOSE',
            'MEMBER_INVITE', 'MEMBER_READ', 'MEMBER_SUSPEND', 'MEMBER_REVOKE',
            'ROLE_READ', 'ROLE_ASSIGN', 'ROLE_REVOKE', 'SECURITY_AUDIT_READ',
            'PRODUCT_CREATE', 'PRODUCT_UPDATE', 'PRODUCT_ACTIVATE',
        ], array_column(PermissionCode::cases(), 'value'));
    }
}
