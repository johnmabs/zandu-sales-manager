<?php

declare(strict_types=1);

namespace Zandu\SharedKernel\Access;

enum PermissionCode: string
{
    case OrganizationRead = 'ORGANIZATION_READ';
    case OrganizationUpdate = 'ORGANIZATION_UPDATE';
    case OrganizationSuspend = 'ORGANIZATION_SUSPEND';

    case StoreCreate = 'STORE_CREATE';
    case StoreRead = 'STORE_READ';
    case StoreUpdate = 'STORE_UPDATE';
    case StoreSuspend = 'STORE_SUSPEND';
    case StoreClose = 'STORE_CLOSE';

    case MemberInvite = 'MEMBER_INVITE';
    case MemberRead = 'MEMBER_READ';
    case MemberSuspend = 'MEMBER_SUSPEND';
    case MemberRevoke = 'MEMBER_REVOKE';

    case RoleRead = 'ROLE_READ';
    case RoleAssign = 'ROLE_ASSIGN';
    case RoleRevoke = 'ROLE_REVOKE';

    case SecurityAuditRead = 'SECURITY_AUDIT_READ';

    case CatalogRead = 'CATALOG_READ';

    case UnitOfMeasureCreate = 'UNIT_OF_MEASURE_CREATE';
    case UnitOfMeasureUpdate = 'UNIT_OF_MEASURE_UPDATE';
    case UnitOfMeasureActivate = 'UNIT_OF_MEASURE_ACTIVATE';
    case UnitOfMeasureDeactivate = 'UNIT_OF_MEASURE_DEACTIVATE';

    case CategoryCreate = 'CATEGORY_CREATE';
    case CategoryUpdate = 'CATEGORY_UPDATE';
    case CategoryArchive = 'CATEGORY_ARCHIVE';

    case ProductCreate = 'PRODUCT_CREATE';
    case ProductRead = 'PRODUCT_READ';
    case ProductUpdate = 'PRODUCT_UPDATE';
    case ProductActivate = 'PRODUCT_ACTIVATE';
    case ProductDeactivate = 'PRODUCT_DEACTIVATE';
    case ProductArchive = 'PRODUCT_ARCHIVE';

    case PriceListCreate = 'PRICE_LIST_CREATE';
    case PriceListRead = 'PRICE_LIST_READ';
    case PriceListUpdate = 'PRICE_LIST_UPDATE';
    case PriceListActivate = 'PRICE_LIST_ACTIVATE';
    case PriceListArchive = 'PRICE_LIST_ARCHIVE';

    case ProductPriceCreate = 'PRODUCT_PRICE_CREATE';
    case ProductPriceRead = 'PRODUCT_PRICE_READ';
    case ProductPriceUpdate = 'PRODUCT_PRICE_UPDATE';
    case ProductPriceArchive = 'PRODUCT_PRICE_ARCHIVE';

    case InventoryRead = 'INVENTORY_READ';
    case InventoryInitialize = 'INVENTORY_INITIALIZE';
    case InventoryAdjust = 'INVENTORY_ADJUST';
    case StockMovementRead = 'STOCK_MOVEMENT_READ';
    case InventoryCostingInitialize = 'INVENTORY_COSTING_INITIALIZE';

    case CashRegisterCreate = 'CASH_REGISTER_CREATE';
    case CashRegisterRead = 'CASH_REGISTER_READ';
    case CashRegisterUpdate = 'CASH_REGISTER_UPDATE';
    case CashRegisterManage = 'CASH_REGISTER_MANAGE';
    case CashSessionOpen = 'CASH_SESSION_OPEN';
    case CashSessionRead = 'CASH_SESSION_READ';
    case CashSessionClose = 'CASH_SESSION_CLOSE';
    case CashMovementRead = 'CASH_MOVEMENT_READ';
    case CashInRecord = 'CASH_IN_RECORD';
    case CashOutRecord = 'CASH_OUT_RECORD';
    case CashWithdrawalRecord = 'CASH_WITHDRAWAL_RECORD';

    case SaleCreate = 'SALE_CREATE';
    case SaleRead = 'SALE_READ';
    case SaleUpdateDraft = 'SALE_UPDATE_DRAFT';
    case SaleCancelDraft = 'SALE_CANCEL_DRAFT';
    case SaleComplete = 'SALE_COMPLETE';
    case SalePriceOverride = 'SALE_PRICE_OVERRIDE';
}
