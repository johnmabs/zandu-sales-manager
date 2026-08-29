<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Domain\StockMovement;

enum StockMovementType: string
{
    case InitialStock = 'INITIAL_STOCK';
    case AdjustmentIn = 'ADJUSTMENT_IN';
    case AdjustmentOut = 'ADJUSTMENT_OUT';
    case Sale = 'SALE';
    case SaleReturn = 'SALE_RETURN';
    case PurchaseReceipt = 'PURCHASE_RECEIPT';
    case GoodsReceiptCorrectionIn = 'GOODS_RECEIPT_CORRECTION_IN';
    case GoodsReceiptCorrectionOut = 'GOODS_RECEIPT_CORRECTION_OUT';

    public function isIncrease(): bool
    {
        return !in_array($this, [self::AdjustmentOut, self::Sale, self::GoodsReceiptCorrectionOut], true);
    }
}
