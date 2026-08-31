<?php

declare(strict_types=1);

namespace Zandu\Modules\InventoryCosting\Domain\ValuationMovement;

enum StockValuationMovementType: string
{
    case Opening = 'OPENING';
    case InitialStock = 'INITIAL_STOCK';
    case AdjustmentIn = 'ADJUSTMENT_IN';
    case AdjustmentOut = 'ADJUSTMENT_OUT';
    case Sale = 'SALE';
    case SaleReturn = 'SALE_RETURN';
    case PurchaseReceipt = 'PURCHASE_RECEIPT';
    case GoodsReceiptCorrectionIn = 'GOODS_RECEIPT_CORRECTION_IN';
    case GoodsReceiptCorrectionOut = 'GOODS_RECEIPT_CORRECTION_OUT';
    case PurchaseReturn = 'PURCHASE_RETURN';
    case TransferOut = 'TRANSFER_OUT';
    case TransferIn = 'TRANSFER_IN';

    public function isIncrease(): bool
    {
        return in_array($this, [self::Opening, self::InitialStock, self::AdjustmentIn, self::SaleReturn, self::PurchaseReceipt, self::GoodsReceiptCorrectionIn, self::TransferIn], true);
    }

    public function requiresStockMovement(): bool
    {
        return self::Opening !== $this;
    }

    public function sourceType(): string
    {
        return match ($this) {
            self::Opening => 'BOOTSTRAP',
            self::InitialStock => 'INITIALIZATION',
            self::AdjustmentIn, self::AdjustmentOut => 'MANUAL_ADJUSTMENT',
            self::Sale => 'SALE',
            self::SaleReturn => 'RETURN',
            self::PurchaseReceipt => 'GOODS_RECEIPT',
            self::GoodsReceiptCorrectionIn, self::GoodsReceiptCorrectionOut => 'GOODS_RECEIPT_CORRECTION',
            self::PurchaseReturn => 'PURCHASE_RETURN',
            self::TransferOut, self::TransferIn => 'TRANSFER',
        };
    }
}
