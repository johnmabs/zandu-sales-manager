<?php

declare(strict_types=1);

namespace Zandu\SharedKernel\SecurityAudit;

enum SecurityAction: string
{
    case OrganizationCreated = 'ORGANIZATION_CREATED';
    case OrganizationUpdated = 'ORGANIZATION_UPDATED';
    case OrganizationSuspended = 'ORGANIZATION_SUSPENDED';
    case OrganizationReactivated = 'ORGANIZATION_REACTIVATED';
    case StoreCreated = 'STORE_CREATED';
    case StoreUpdated = 'STORE_UPDATED';
    case StoreSuspended = 'STORE_SUSPENDED';
    case StoreReactivated = 'STORE_REACTIVATED';
    case MemberInvited = 'MEMBER_INVITED';
    case MemberSuspended = 'MEMBER_SUSPENDED';
    case MemberReactivated = 'MEMBER_REACTIVATED';
    case MemberRevoked = 'MEMBER_REVOKED';
    case RoleAssigned = 'ROLE_ASSIGNED';
    case RoleRemoved = 'ROLE_REMOVED';
    case OwnerAssigned = 'OWNER_ASSIGNED';
    case OwnerRemoved = 'OWNER_REMOVED';
    case AuthorizationDenied = 'AUTHORIZATION_DENIED';
    case ProductActivated = 'PRODUCT_ACTIVATED';
    case ProductArchived = 'PRODUCT_ARCHIVED';
    case ProductPriceUpdated = 'PRODUCT_PRICE_UPDATED';
    case PriceListActivated = 'PRICE_LIST_ACTIVATED';
    case CategoryArchived = 'CATEGORY_ARCHIVED';
    case StockInitialized = 'STOCK_INITIALIZED';
    case StockAdjusted = 'STOCK_ADJUSTED';
    case StockValuationInitialized = 'STOCK_VALUATION_INITIALIZED';
    case CashRegisterArchived = 'CASH_REGISTER_ARCHIVED';
    case CashSessionOpened = 'CASH_SESSION_OPENED';
    case CashSessionClosed = 'CASH_SESSION_CLOSED';
    case CashInRecorded = 'CASH_IN_RECORDED';
    case CashOutRecorded = 'CASH_OUT_RECORDED';
    case CashWithdrawalRecorded = 'CASH_WITHDRAWAL_RECORDED';
    case SaleCompleted = 'SALE_COMPLETED';
    case PaymentRefundConfirmed = 'PAYMENT_REFUND_CONFIRMED';
    case SaleReturnCreated = 'SALE_RETURN_CREATED';
    case SaleReturnLineAdded = 'SALE_RETURN_LINE_ADDED';
    case SaleReturnCompleted = 'SALE_RETURN_COMPLETED';
    case SaleReturnCancelled = 'SALE_RETURN_CANCELLED';
    case PartialPurchaseOrderClosed = 'PARTIAL_PURCHASE_ORDER_CLOSED';
    case GoodsReceiptPosted = 'GOODS_RECEIPT_POSTED';
    case OverReceiptAuthorized = 'OVER_RECEIPT_AUTHORIZED';
    case GoodsReceiptCorrected = 'GOODS_RECEIPT_CORRECTED';
    case PurchaseReturnShipped = 'PURCHASE_RETURN_SHIPPED';
    case PurchaseReturnCancelled = 'PURCHASE_RETURN_CANCELLED';
}
