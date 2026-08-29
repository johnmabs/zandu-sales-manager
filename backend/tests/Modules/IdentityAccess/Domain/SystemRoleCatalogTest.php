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
        self::assertSame([
            PermissionCode::OrganizationRead,
            PermissionCode::StoreCreate,
            PermissionCode::StoreRead,
            PermissionCode::StoreUpdate,
            PermissionCode::StoreSuspend,
            PermissionCode::StoreClose,
            PermissionCode::CatalogRead,
            PermissionCode::ProductRead,
            PermissionCode::SupplierRead,
            PermissionCode::PurchaseOrderCreate,
            PermissionCode::PurchaseOrderRead,
            PermissionCode::PurchaseOrderUpdateDraft,
            PermissionCode::PurchaseOrderConfirm,
            PermissionCode::PurchaseOrderCancel,
            PermissionCode::PurchaseOrderClose,
            PermissionCode::GoodsReceiptCreate,
            PermissionCode::GoodsReceiptRead,
            PermissionCode::GoodsReceiptPost,
            PermissionCode::GoodsReceiptCancel,
            PermissionCode::PurchaseReturnCreate,
            PermissionCode::PurchaseReturnRead,
            PermissionCode::PurchaseReturnShip,
            PermissionCode::PurchaseReturnCancel,
            PermissionCode::PriceListRead,
            PermissionCode::ProductPriceRead,
            PermissionCode::InventoryRead,
            PermissionCode::InventoryAdjust,
            PermissionCode::StockMovementRead,
            PermissionCode::CashRegisterRead,
            PermissionCode::CashRegisterManage,
            PermissionCode::CashSessionRead,
            PermissionCode::CashMovementRead,
            PermissionCode::SaleCreate,
            PermissionCode::SaleRead,
            PermissionCode::SaleUpdateDraft,
            PermissionCode::SaleCancelDraft,
            PermissionCode::SaleComplete,
            PermissionCode::PaymentRefundCreate,
            PermissionCode::PaymentRefundRead,
            PermissionCode::SaleReturnCreate,
            PermissionCode::SaleReturnRead,
            PermissionCode::SaleReturnComplete,
            PermissionCode::SaleReturnCancel,
        ], $roles[1]->permissions());
        self::assertSame([
            PermissionCode::StoreRead,
            PermissionCode::CatalogRead,
            PermissionCode::ProductRead,
            PermissionCode::ProductPriceRead,
            PermissionCode::InventoryRead,
            PermissionCode::CashSessionOpen,
            PermissionCode::CashSessionClose,
            PermissionCode::CashMovementRead,
            PermissionCode::CashInRecord,
            PermissionCode::CashOutRecord,
            PermissionCode::SaleCreate,
            PermissionCode::SaleRead,
            PermissionCode::SaleUpdateDraft,
            PermissionCode::SaleCancelDraft,
            PermissionCode::SaleComplete,
            PermissionCode::PaymentRefundCreate,
            PermissionCode::PaymentRefundRead,
            PermissionCode::SaleReturnCreate,
            PermissionCode::SaleReturnRead,
            PermissionCode::SaleReturnComplete,
            PermissionCode::SaleReturnCancel,
        ], $roles[2]->permissions());
        self::assertSame([
            PermissionCode::OrganizationRead,
            PermissionCode::StoreRead,
            PermissionCode::SecurityAuditRead,
            PermissionCode::PriceListRead,
            PermissionCode::ProductPriceRead,
            PermissionCode::InventoryRead,
            PermissionCode::StockMovementRead,
            PermissionCode::CashRegisterRead,
            PermissionCode::CashSessionRead,
            PermissionCode::CashMovementRead,
            PermissionCode::SaleRead,
            PermissionCode::PaymentRefundRead,
            PermissionCode::SaleReturnRead,
            PermissionCode::SupplierRead,
            PermissionCode::PurchaseOrderRead,
            PermissionCode::GoodsReceiptRead,
            PermissionCode::PurchaseReturnRead,
        ], $roles[3]->permissions());

        foreach (array_slice($roles, 1) as $nonOwnerRole) {
            self::assertFalse($nonOwnerRole->grants(PermissionCode::UnitOfMeasureCreate));
            self::assertFalse($nonOwnerRole->grants(PermissionCode::UnitOfMeasureUpdate));
            self::assertFalse($nonOwnerRole->grants(PermissionCode::UnitOfMeasureActivate));
            self::assertFalse($nonOwnerRole->grants(PermissionCode::UnitOfMeasureDeactivate));
            self::assertFalse($nonOwnerRole->grants(PermissionCode::CategoryCreate));
            self::assertFalse($nonOwnerRole->grants(PermissionCode::ProductUpdate));
            self::assertFalse($nonOwnerRole->grants(PermissionCode::PriceListUpdate));
            self::assertFalse($nonOwnerRole->grants(PermissionCode::ProductPriceUpdate));
            self::assertFalse($nonOwnerRole->grants(PermissionCode::InventoryCostingInitialize));
            self::assertFalse($nonOwnerRole->grants(PermissionCode::SupplierCreate));
            self::assertFalse($nonOwnerRole->grants(PermissionCode::SupplierUpdate));
            self::assertFalse($nonOwnerRole->grants(PermissionCode::SupplierArchive));
            self::assertFalse($nonOwnerRole->grants(PermissionCode::PurchasingOverReceipt));
            self::assertFalse($nonOwnerRole->grants(PermissionCode::PurchasingReceiptCorrect));
        }

        self::assertTrue($roles[1]->grants(PermissionCode::SupplierRead));
        self::assertFalse($roles[2]->grants(PermissionCode::SupplierRead));
        self::assertTrue($roles[3]->grants(PermissionCode::SupplierRead));
        self::assertTrue($roles[1]->grants(PermissionCode::PurchaseOrderConfirm));
        self::assertFalse($roles[2]->grants(PermissionCode::PurchaseOrderRead));
        self::assertTrue($roles[3]->grants(PermissionCode::PurchaseOrderRead));
        self::assertFalse($roles[3]->grants(PermissionCode::PurchaseOrderConfirm));
        self::assertTrue($roles[1]->grants(PermissionCode::GoodsReceiptCreate));
        self::assertFalse($roles[2]->grants(PermissionCode::GoodsReceiptRead));
        self::assertTrue($roles[3]->grants(PermissionCode::GoodsReceiptRead));
        self::assertFalse($roles[3]->grants(PermissionCode::GoodsReceiptPost));
    }
}
