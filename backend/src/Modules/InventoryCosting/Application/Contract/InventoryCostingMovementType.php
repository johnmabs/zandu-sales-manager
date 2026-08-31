<?php

declare(strict_types=1);

namespace Zandu\Modules\InventoryCosting\Application\Contract;

enum InventoryCostingMovementType: string
{
    case InitialStock = 'INITIAL_STOCK';
    case AdjustmentIn = 'ADJUSTMENT_IN';
    case AdjustmentOut = 'ADJUSTMENT_OUT';
    case Sale = 'SALE';
    case SaleReturn = 'SALE_RETURN';
    case PurchaseReceipt = 'PURCHASE_RECEIPT';
    case GoodsReceiptCorrectionIn = 'GOODS_RECEIPT_CORRECTION_IN';
    case GoodsReceiptCorrectionOut = 'GOODS_RECEIPT_CORRECTION_OUT';
    case PurchaseReturn = 'PURCHASE_RETURN';

    public function isIncoming(): bool
    {
        return !in_array($this, [self::AdjustmentOut, self::Sale, self::GoodsReceiptCorrectionOut, self::PurchaseReturn], true);
    }
}
