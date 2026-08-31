<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Domain\StockCount;

use DateTimeImmutable;
use DateTimeZone;
use Zandu\Modules\Inventory\Domain\InventoryRuleViolation;
use Zandu\SharedKernel\Identity\{ActorId, OrganizationId, ProductId, StockCountId, StockCountLineId, StoreId};
use Zandu\SharedKernel\Quantity\Quantity;

final class StockCountLine
{
    private function __construct(
        private readonly StockCountLineId $id,
        private readonly StockCountId $stockCountId,
        private readonly OrganizationId $organizationId,
        private readonly StoreId $storeId,
        private readonly ProductId $productId,
        private readonly Quantity $expectedQuantity,
        private ?Quantity $countedQuantity,
        private ?ActorId $countedBy,
        private ?DateTimeImmutable $countedAt,
        private int $revision,
        private StockCountReconciliationStatus $reconciliationStatus,
        private int $version,
    ) {}

    public static function create(StockCountLineId $id, StockCountId $stockCountId, OrganizationId $organizationId, StoreId $storeId, ProductId $productId, Quantity $expectedQuantity): self
    {
        if ($expectedQuantity->isNegative()) {
            throw InventoryRuleViolation::with('STOCK_COUNT_EXPECTED_QUANTITY_INVALID', 'Stock count expected quantity cannot be negative.');
        }

        return new self($id, $stockCountId, $organizationId, $storeId, $productId, $expectedQuantity, null, null, null, 0, StockCountReconciliationStatus::Pending, 1);
    }

    public static function reconstitute(StockCountLineId $id, StockCountId $stockCountId, OrganizationId $organizationId, StoreId $storeId, ProductId $productId, Quantity $expectedQuantity, ?Quantity $countedQuantity, ?ActorId $countedBy, ?DateTimeImmutable $countedAt, int $revision, StockCountReconciliationStatus $reconciliationStatus, int $version): self
    {
        return new self($id, $stockCountId, $organizationId, $storeId, $productId, $expectedQuantity, $countedQuantity, $countedBy, $countedAt?->setTimezone(new DateTimeZone('UTC')), $revision, $reconciliationStatus, $version);
    }

    public function record(Quantity $countedQuantity, ActorId $actorId, DateTimeImmutable $at): void
    {
        if ($countedQuantity->isNegative()) {
            throw InventoryRuleViolation::with('STOCK_COUNT_COUNTED_QUANTITY_INVALID', 'Stock count quantity cannot be negative.');
        }
        if (StockCountReconciliationStatus::Pending !== $this->reconciliationStatus) {
            throw InventoryRuleViolation::with('STOCK_COUNT_LINE_RECONCILED', 'A reconciled stock count line cannot be changed.');
        }
        $this->countedQuantity = $countedQuantity;
        $this->countedBy = $actorId;
        $this->countedAt = $at->setTimezone(new DateTimeZone('UTC'));
        ++$this->revision;
        ++$this->version;
    }

    public function id(): StockCountLineId
    {
        return $this->id;
    }
    public function stockCountId(): StockCountId
    {
        return $this->stockCountId;
    }
    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }
    public function storeId(): StoreId
    {
        return $this->storeId;
    }
    public function productId(): ProductId
    {
        return $this->productId;
    }
    public function expectedQuantity(): Quantity
    {
        return $this->expectedQuantity;
    }
    public function countedQuantity(): ?Quantity
    {
        return $this->countedQuantity;
    }
    public function countedBy(): ?ActorId
    {
        return $this->countedBy;
    }
    public function countedAt(): ?DateTimeImmutable
    {
        return $this->countedAt;
    }
    public function revision(): int
    {
        return $this->revision;
    }
    public function reconciliationStatus(): StockCountReconciliationStatus
    {
        return $this->reconciliationStatus;
    }
    public function version(): int
    {
        return $this->version;
    }
}
