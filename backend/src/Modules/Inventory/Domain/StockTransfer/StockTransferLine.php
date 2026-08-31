<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Domain\StockTransfer;

use Zandu\Modules\Inventory\Domain\InventoryRuleViolation;
use Zandu\SharedKernel\Identity\{ProductId, StockTransferId, StockTransferLineId};
use Zandu\SharedKernel\Money\Money;
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class StockTransferLine
{
    public function __construct(
        private StockTransferLineId $id,
        private StockTransferId $stockTransferId,
        private ProductId $productId,
        private Quantity $requestedQuantity,
        private ?Quantity $shippedQuantity = null,
        private ?Quantity $receivedQuantity = null,
        private ?Money $shippedUnitCostSnapshot = null,
        private ?Money $shippedValueSnapshot = null,
        private ?Money $receivedValueSnapshot = null,
    ) {
        if ($requestedQuantity->isZero() || $requestedQuantity->isNegative()) {
            throw InventoryRuleViolation::with('STOCK_TRANSFER_QUANTITY_INVALID', 'Requested transfer quantity must be greater than zero.');
        }
    }

    public function id(): StockTransferLineId
    {
        return $this->id;
    }
    public function stockTransferId(): StockTransferId
    {
        return $this->stockTransferId;
    }
    public function productId(): ProductId
    {
        return $this->productId;
    }
    public function requestedQuantity(): Quantity
    {
        return $this->requestedQuantity;
    }
    public function shippedQuantity(): ?Quantity
    {
        return $this->shippedQuantity;
    }
    public function receivedQuantity(): ?Quantity
    {
        return $this->receivedQuantity;
    }

    public function transitDiscrepancy(): ?Quantity
    {
        if (null === $this->shippedQuantity || null === $this->receivedQuantity) {
            return null;
        }

        return $this->shippedQuantity->subtract($this->receivedQuantity);
    }
    public function shippedUnitCostSnapshot(): ?Money
    {
        return $this->shippedUnitCostSnapshot;
    }
    public function shippedValueSnapshot(): ?Money
    {
        return $this->shippedValueSnapshot;
    }
    public function receivedValueSnapshot(): ?Money
    {
        return $this->receivedValueSnapshot;
    }
    public function transitLossValue(): ?Money
    {
        return null === $this->shippedValueSnapshot || null === $this->receivedValueSnapshot ? null : $this->shippedValueSnapshot->subtract($this->receivedValueSnapshot);
    }

    public function hasTransitDiscrepancy(): bool
    {
        $discrepancy = $this->transitDiscrepancy();

        return null !== $discrepancy && !$discrepancy->isZero();
    }

    public function withRequestedQuantity(Quantity $requestedQuantity): self
    {
        return new self($this->id, $this->stockTransferId, $this->productId, $requestedQuantity, $this->shippedQuantity, $this->receivedQuantity, $this->shippedUnitCostSnapshot, $this->shippedValueSnapshot, $this->receivedValueSnapshot);
    }

    public function withShippedQuantity(Quantity $shippedQuantity): self
    {
        if ($shippedQuantity->isNegative() || $shippedQuantity->compareTo($this->requestedQuantity) > 0) {
            throw InventoryRuleViolation::with('TRANSFER_SHIPPED_QUANTITY_EXCEEDS_REQUESTED', 'Shipped transfer quantity must be between zero and the requested quantity.');
        }

        return new self($this->id, $this->stockTransferId, $this->productId, $this->requestedQuantity, $shippedQuantity, null);
    }

    public function withReceivedQuantity(Quantity $receivedQuantity): self
    {
        if (null === $this->shippedQuantity) {
            throw InventoryRuleViolation::with('TRANSFER_NOT_SHIPPED', 'A stock transfer line must be shipped before it can be received.');
        }
        if ($receivedQuantity->isNegative() || $receivedQuantity->compareTo($this->shippedQuantity) > 0) {
            throw InventoryRuleViolation::with('TRANSFER_RECEIVED_QUANTITY_EXCEEDS_SHIPPED', 'Received transfer quantity must be between zero and the shipped quantity.');
        }

        return new self($this->id, $this->stockTransferId, $this->productId, $this->requestedQuantity, $this->shippedQuantity, $receivedQuantity, $this->shippedUnitCostSnapshot, $this->shippedValueSnapshot);
    }

    public function withShipmentCost(Money $unitCost, Money $totalValue): self
    {
        if (null === $this->shippedQuantity || $this->shippedQuantity->isZero() || $unitCost->amount()->isNegative() || $totalValue->amount()->isNegative()) {
            throw InventoryRuleViolation::with('TRANSFER_COST_SNAPSHOT_INVALID', 'Shipment cost snapshots require a positive shipped quantity and non-negative values.');
        }
        return new self($this->id, $this->stockTransferId, $this->productId, $this->requestedQuantity, $this->shippedQuantity, $this->receivedQuantity, $unitCost, $totalValue, $this->receivedValueSnapshot);
    }

    public function withReceivedValue(Money $receivedValue): self
    {
        if (null === $this->receivedQuantity || null === $this->shippedValueSnapshot || $receivedValue->amount()->isNegative() || $receivedValue->compareTo($this->shippedValueSnapshot) > 0) {
            throw InventoryRuleViolation::with('TRANSFER_RECEIVED_VALUE_INVALID', 'Received transfer value must be between zero and the shipped value.');
        }
        return new self($this->id, $this->stockTransferId, $this->productId, $this->requestedQuantity, $this->shippedQuantity, $this->receivedQuantity, $this->shippedUnitCostSnapshot, $this->shippedValueSnapshot, $receivedValue);
    }
}
