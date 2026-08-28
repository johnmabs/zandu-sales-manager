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
            'CATALOG_READ',
            'UNIT_OF_MEASURE_CREATE', 'UNIT_OF_MEASURE_UPDATE', 'UNIT_OF_MEASURE_ACTIVATE', 'UNIT_OF_MEASURE_DEACTIVATE',
            'CATEGORY_CREATE', 'CATEGORY_UPDATE', 'CATEGORY_ARCHIVE',
            'PRODUCT_CREATE', 'PRODUCT_READ', 'PRODUCT_UPDATE', 'PRODUCT_ACTIVATE', 'PRODUCT_DEACTIVATE', 'PRODUCT_ARCHIVE',
            'PRICE_LIST_CREATE', 'PRICE_LIST_READ', 'PRICE_LIST_UPDATE', 'PRICE_LIST_ACTIVATE', 'PRICE_LIST_ARCHIVE',
            'PRODUCT_PRICE_CREATE', 'PRODUCT_PRICE_READ', 'PRODUCT_PRICE_UPDATE', 'PRODUCT_PRICE_ARCHIVE',
            'INVENTORY_READ', 'INVENTORY_INITIALIZE', 'INVENTORY_ADJUST', 'STOCK_MOVEMENT_READ',
            'INVENTORY_COSTING_INITIALIZE',
            'CASH_REGISTER_CREATE', 'CASH_REGISTER_READ', 'CASH_REGISTER_UPDATE', 'CASH_REGISTER_MANAGE',
            'CASH_SESSION_OPEN', 'CASH_SESSION_READ', 'CASH_SESSION_CLOSE', 'CASH_MOVEMENT_READ',
            'CASH_IN_RECORD', 'CASH_OUT_RECORD', 'CASH_WITHDRAWAL_RECORD',
            'SALE_CREATE', 'SALE_READ', 'SALE_UPDATE_DRAFT', 'SALE_CANCEL_DRAFT', 'SALE_COMPLETE', 'SALE_PRICE_OVERRIDE',
            'PAYMENT_REFUND_CREATE', 'PAYMENT_REFUND_READ',
        ], array_column(PermissionCode::cases(), 'value'));

        foreach (PermissionCode::cases() as $permission) {
            self::assertDoesNotMatchRegularExpression('/^PURCHASE_/', $permission->value);
        }
    }
}
