<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Domain\PurchaseOrder;

use Zandu\Modules\Purchasing\Domain\PurchasingRuleViolation;
use Zandu\SharedKernel\Decimal\RoundingMode;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;
use Zandu\SharedKernel\Identity\PurchaseOrderId;
use Zandu\SharedKernel\Identity\PurchaseOrderLineId;
use Zandu\SharedKernel\Money\Money;
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class PurchaseOrderLine
{
    public function __construct(
        private PurchaseOrderLineId $id,
        private PurchaseOrderId $purchaseOrderId,
        private ProductId $productId,
        private ?ProductPackagingId $productPackagingId,
        private Quantity $enteredOrderedQuantity,
        private Quantity $conversionFactorSnapshot,
        private Quantity $orderedBaseQuantity,
        private Money $unitCost,
        private Money $inventoryUnitCost,
        private Quantity $receivedQuantity,
    ) {
        if ($enteredOrderedQuantity->isZero() || $enteredOrderedQuantity->isNegative()) {
            throw PurchasingRuleViolation::with('PURCHASE_ORDER_QUANTITY_INVALID', 'Ordered quantity must be positive.');
        }
        if ($conversionFactorSnapshot->isZero() || $conversionFactorSnapshot->isNegative()) {
            throw PurchasingRuleViolation::with('PURCHASE_ORDER_CONVERSION_FACTOR_INVALID', 'Conversion factor must be positive.');
        }
        $calculatedBaseQuantity = $enteredOrderedQuantity->multiply(
            $conversionFactorSnapshot->value(),
            12,
            RoundingMode::HalfEven,
        );
        if (!$orderedBaseQuantity->equals($calculatedBaseQuantity)) {
            throw PurchasingRuleViolation::with('PURCHASE_ORDER_BASE_QUANTITY_INVALID', 'Base quantity must match entered quantity multiplied by its conversion factor.');
        }
        if ($unitCost->amount()->isNegative() || $inventoryUnitCost->amount()->isNegative()) {
            throw PurchasingRuleViolation::with('PURCHASE_ORDER_COST_INVALID', 'Purchase order costs cannot be negative.');
        }
        if (!$unitCost->currency()->equals($inventoryUnitCost->currency())) {
            throw PurchasingRuleViolation::with('PURCHASE_ORDER_CURRENCY_MISMATCH', 'Line costs must use the same currency.');
        }
        $calculatedInventoryUnitCost = $unitCost->divide(
            $conversionFactorSnapshot->value(),
            12,
            RoundingMode::HalfEven,
        );
        if (!$inventoryUnitCost->equals($calculatedInventoryUnitCost)) {
            throw PurchasingRuleViolation::with('PURCHASE_ORDER_INVENTORY_COST_INVALID', 'Inventory unit cost must match unit cost divided by the conversion factor.');
        }
        if ($receivedQuantity->isNegative() || $receivedQuantity->compareTo($orderedBaseQuantity) > 0) {
            throw PurchasingRuleViolation::with('PURCHASE_ORDER_RECEIVED_QUANTITY_INVALID', 'Received quantity must be between zero and the ordered base quantity.');
        }
    }

    public function expectedTotal(): Money
    {
        return $this->unitCost->multiply($this->enteredOrderedQuantity->value(), 6, RoundingMode::HalfEven);
    }

    public function withAdditionalReceipt(Quantity $quantity): self
    {
        if ($quantity->isZero() || $quantity->isNegative()) {
            throw PurchasingRuleViolation::with('PURCHASE_ORDER_RECEIPT_QUANTITY_INVALID', 'Received quantity increment must be positive.');
        }
        $receivedQuantity = $this->receivedQuantity->add($quantity);
        if ($receivedQuantity->compareTo($this->orderedBaseQuantity) > 0) {
            throw PurchasingRuleViolation::with('OVER_RECEIPT_NOT_ALLOWED', 'Received quantity cannot exceed ordered base quantity.');
        }

        return new self(
            $this->id,
            $this->purchaseOrderId,
            $this->productId,
            $this->productPackagingId,
            $this->enteredOrderedQuantity,
            $this->conversionFactorSnapshot,
            $this->orderedBaseQuantity,
            $this->unitCost,
            $this->inventoryUnitCost,
            $receivedQuantity,
        );
    }

    public function id(): PurchaseOrderLineId
    {
        return $this->id;
    }
    public function purchaseOrderId(): PurchaseOrderId
    {
        return $this->purchaseOrderId;
    }
    public function productId(): ProductId
    {
        return $this->productId;
    }
    public function productPackagingId(): ?ProductPackagingId
    {
        return $this->productPackagingId;
    }
    public function enteredOrderedQuantity(): Quantity
    {
        return $this->enteredOrderedQuantity;
    }
    public function conversionFactorSnapshot(): Quantity
    {
        return $this->conversionFactorSnapshot;
    }
    public function orderedBaseQuantity(): Quantity
    {
        return $this->orderedBaseQuantity;
    }
    public function unitCost(): Money
    {
        return $this->unitCost;
    }
    public function inventoryUnitCost(): Money
    {
        return $this->inventoryUnitCost;
    }
    public function receivedQuantity(): Quantity
    {
        return $this->receivedQuantity;
    }
}
