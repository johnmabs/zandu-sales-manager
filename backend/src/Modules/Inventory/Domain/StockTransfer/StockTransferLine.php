<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Domain\StockTransfer;

use Zandu\Modules\Inventory\Domain\InventoryRuleViolation;
use Zandu\SharedKernel\Identity\{ProductId, StockTransferId, StockTransferLineId};
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

    public function withRequestedQuantity(Quantity $requestedQuantity): self
    {
        return new self($this->id, $this->stockTransferId, $this->productId, $requestedQuantity, $this->shippedQuantity, $this->receivedQuantity);
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

        return new self($this->id, $this->stockTransferId, $this->productId, $this->requestedQuantity, $this->shippedQuantity, $receivedQuantity);
    }
}
