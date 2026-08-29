<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Domain\Access;

use LogicException;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Identity\RoleId;
use Zandu\SharedKernel\Identity\UuidFactory;

final readonly class SystemRoleCatalog
{
    private const string ORGANIZATION_OWNER_ID = '00000000-0000-7000-8000-000000000001';
    private const string STORE_MANAGER_ID = '00000000-0000-7000-8000-000000000002';
    private const string CASHIER_ID = '00000000-0000-7000-8000-000000000003';
    private const string ACCOUNTANT_ID = '00000000-0000-7000-8000-000000000004';

    public function __construct(private UuidFactory $uuidFactory) {}

    /** @return non-empty-list<Role> */
    public function roles(): array
    {
        return [
            Role::system(
                $this->roleId(self::ORGANIZATION_OWNER_ID),
                RoleCode::organizationOwner(),
                'Organization owner',
                'Full administration access within the organization.',
                PermissionCode::cases(),
            ),
            Role::system(
                $this->roleId(self::STORE_MANAGER_ID),
                RoleCode::fromString(RoleCode::STORE_MANAGER),
                'Store manager',
                'Manages stores and reads catalog and pricing within the assigned scope.',
                [
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
                ],
            ),
            Role::system(
                $this->roleId(self::CASHIER_ID),
                RoleCode::fromString(RoleCode::CASHIER),
                'Cashier',
                'Reads stores, catalog and resolved product prices within the assigned scope.',
                [
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
                ],
            ),
            Role::system(
                $this->roleId(self::ACCOUNTANT_ID),
                RoleCode::fromString(RoleCode::ACCOUNTANT),
                'Accountant',
                'Reads organization, store and security audit information.',
                [
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
                ],
            ),
        ];
    }

    public function organizationOwnerRoleId(): RoleId
    {
        return $this->roleId(self::ORGANIZATION_OWNER_ID);
    }

    public function get(RoleCode $code): Role
    {
        foreach ($this->roles() as $role) {
            if ($role->code()->equals($code)) {
                return $role;
            }
        }

        throw new LogicException(sprintf('Unknown system role "%s".', $code->value()));
    }

    public function getById(RoleId $roleId): Role
    {
        foreach ($this->roles() as $role) {
            if ($role->id()->equals($roleId)) {
                return $role;
            }
        }

        throw new LogicException(sprintf('Unknown system role "%s".', $roleId->toString()));
    }

    public function isOrganizationOwner(RoleId $roleId): bool
    {
        return $this->organizationOwnerRoleId()->equals($roleId);
    }

    private function roleId(string $value): RoleId
    {
        return RoleId::fromString($value, $this->uuidFactory);
    }
}
