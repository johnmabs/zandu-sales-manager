<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Domain\GoodsReceipt;

use Zandu\Modules\Purchasing\Domain\PurchasingRuleViolation;
use Zandu\SharedKernel\Decimal\RoundingMode;
use Zandu\SharedKernel\Identity\GoodsReceiptId;
use Zandu\SharedKernel\Identity\GoodsReceiptLineId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;
use Zandu\SharedKernel\Identity\PurchaseOrderLineId;
use Zandu\SharedKernel\Money\Money;
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class GoodsReceiptLine
{
    public function __construct(
        private GoodsReceiptLineId $id,
        private GoodsReceiptId $goodsReceiptId,
        private ProductId $productId,
        private ?ProductPackagingId $productPackagingId,
        private Quantity $enteredReceivedQuantity,
        private Quantity $conversionFactorSnapshot,
        private Quantity $receivedBaseQuantity,
        private ?Money $actualUnitCost,
        private Money $inventoryUnitCost,
        private ?PurchaseOrderLineId $purchaseOrderLineId,
    ) {
        if ($enteredReceivedQuantity->isZero() || $enteredReceivedQuantity->isNegative()) {
            throw PurchasingRuleViolation::with('GOODS_RECEIPT_QUANTITY_INVALID', 'Received quantity must be positive.');
        }
        if ($conversionFactorSnapshot->isZero() || $conversionFactorSnapshot->isNegative()) {
            throw PurchasingRuleViolation::with('GOODS_RECEIPT_CONVERSION_FACTOR_INVALID', 'Conversion factor must be positive.');
        }
        $calculatedBaseQuantity = $enteredReceivedQuantity->multiply($conversionFactorSnapshot->value(), 12, RoundingMode::HalfEven);
        if (!$receivedBaseQuantity->equals($calculatedBaseQuantity)) {
            throw PurchasingRuleViolation::with('GOODS_RECEIPT_BASE_QUANTITY_INVALID', 'Base quantity must match received quantity multiplied by its conversion factor.');
        }
        if ($inventoryUnitCost->amount()->isNegative() || (null !== $actualUnitCost && $actualUnitCost->amount()->isNegative())) {
            throw PurchasingRuleViolation::with('GOODS_RECEIPT_COST_INVALID', 'Receipt costs cannot be negative.');
        }
        if (null !== $actualUnitCost) {
            if (!$actualUnitCost->currency()->equals($inventoryUnitCost->currency())) {
                throw PurchasingRuleViolation::with('GOODS_RECEIPT_CURRENCY_MISMATCH', 'Receipt costs must use the same currency.');
            }
            $calculatedInventoryCost = $actualUnitCost->divide($conversionFactorSnapshot->value(), 12, RoundingMode::HalfEven);
            if (!$calculatedInventoryCost->equals($inventoryUnitCost)) {
                throw PurchasingRuleViolation::with('GOODS_RECEIPT_INVENTORY_COST_INVALID', 'Inventory unit cost must match actual unit cost divided by the conversion factor.');
            }
        }
    }

    public function id(): GoodsReceiptLineId
    {
        return $this->id;
    }
    public function goodsReceiptId(): GoodsReceiptId
    {
        return $this->goodsReceiptId;
    }
    public function productId(): ProductId
    {
        return $this->productId;
    }
    public function productPackagingId(): ?ProductPackagingId
    {
        return $this->productPackagingId;
    }
    public function enteredReceivedQuantity(): Quantity
    {
        return $this->enteredReceivedQuantity;
    }
    public function conversionFactorSnapshot(): Quantity
    {
        return $this->conversionFactorSnapshot;
    }
    public function receivedBaseQuantity(): Quantity
    {
        return $this->receivedBaseQuantity;
    }
    public function actualUnitCost(): ?Money
    {
        return $this->actualUnitCost;
    }
    public function inventoryUnitCost(): Money
    {
        return $this->inventoryUnitCost;
    }
    public function purchaseOrderLineId(): ?PurchaseOrderLineId
    {
        return $this->purchaseOrderLineId;
    }
}
