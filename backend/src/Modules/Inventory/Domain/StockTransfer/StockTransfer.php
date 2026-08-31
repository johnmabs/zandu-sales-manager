<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Domain\StockTransfer;

use DateTimeImmutable;
use DateTimeZone;
use Zandu\Modules\Inventory\Domain\InventoryRuleViolation;
use Zandu\SharedKernel\Identity\{ActorId, OrganizationId, StockTransferId, StoreId};

final class StockTransfer
{
    /** @param list<StockTransferLine> $lines */
    private function __construct(private readonly StockTransferId $id, private readonly OrganizationId $organizationId, private readonly StoreId $sourceStoreId, private readonly StoreId $destinationStoreId, private StockTransferStatus $status, private readonly ActorId $createdBy, private readonly DateTimeImmutable $createdAt, private ?ActorId $shippedBy, private ?DateTimeImmutable $shippedAt, private ?string $cancellationReason, private ?ActorId $cancelledBy, private ?DateTimeImmutable $cancelledAt, private int $version, private array $lines) {}

    public static function create(StockTransferId $id, OrganizationId $organizationId, StoreId $sourceStoreId, StoreId $destinationStoreId, ActorId $createdBy, DateTimeImmutable $createdAt): self
    {
        if ($sourceStoreId->equals($destinationStoreId)) {
            throw InventoryRuleViolation::with('STOCK_TRANSFER_SAME_STORE', 'Stock transfer source and destination must differ.');
        }

        return new self($id, $organizationId, $sourceStoreId, $destinationStoreId, StockTransferStatus::Draft, $createdBy, $createdAt->setTimezone(new DateTimeZone('UTC')), null, null, null, null, null, 1, []);
    }

    /** @param list<StockTransferLine> $lines */
    public static function reconstitute(StockTransferId $id, OrganizationId $organizationId, StoreId $sourceStoreId, StoreId $destinationStoreId, StockTransferStatus $status, ActorId $createdBy, DateTimeImmutable $createdAt, ?ActorId $shippedBy, ?DateTimeImmutable $shippedAt, ?string $cancellationReason, ?ActorId $cancelledBy, ?DateTimeImmutable $cancelledAt, int $version, array $lines): self
    {
        return new self($id, $organizationId, $sourceStoreId, $destinationStoreId, $status, $createdBy, $createdAt, $shippedBy, $shippedAt, $cancellationReason, $cancelledBy, $cancelledAt, $version, $lines);
    }

    public function addLine(StockTransferLine $line): void
    {
        if (StockTransferStatus::Draft !== $this->status) {
            throw InventoryRuleViolation::with('STOCK_TRANSFER_NOT_EDITABLE', 'Only a draft stock transfer can be edited.');
        }
        if (!$line->stockTransferId()->equals($this->id)) {
            throw InventoryRuleViolation::with('STOCK_TRANSFER_LINE_SOURCE_INVALID', 'Stock transfer line belongs to another transfer.');
        }
        foreach ($this->lines as $existing) {
            if ($existing->productId()->equals($line->productId())) {
                throw InventoryRuleViolation::with('STOCK_TRANSFER_PRODUCT_DUPLICATE', 'A product can occur only once in a stock transfer.');
            }
        }
        $this->lines[] = $line;
        ++$this->version;
    }

    public function updateLine(\Zandu\SharedKernel\Identity\StockTransferLineId $lineId, \Zandu\SharedKernel\Quantity\Quantity $requestedQuantity): void
    {
        $this->ensureDraft();
        foreach ($this->lines as $index => $line) {
            if ($line->id()->equals($lineId)) {
                $this->lines[$index] = $line->withRequestedQuantity($requestedQuantity);
                ++$this->version;
                return;
            }
        }
        throw InventoryRuleViolation::with('STOCK_TRANSFER_LINE_NOT_FOUND', 'Stock transfer line not found.');
    }

    public function removeLine(\Zandu\SharedKernel\Identity\StockTransferLineId $lineId): void
    {
        $this->ensureDraft();
        foreach ($this->lines as $index => $line) {
            if ($line->id()->equals($lineId)) {
                array_splice($this->lines, $index, 1);
                ++$this->version;
                return;
            }
        }
        throw InventoryRuleViolation::with('STOCK_TRANSFER_LINE_NOT_FOUND', 'Stock transfer line not found.');
    }

    public function cancel(ActorId $actorId, string $reason, DateTimeImmutable $at): void
    {
        $this->ensureDraft();
        $reason = trim($reason);
        if ('' === $reason || mb_strlen($reason) > 500) {
            throw InventoryRuleViolation::with('STOCK_TRANSFER_CANCELLATION_REASON_INVALID', 'Stock transfer cancellation reason must contain between 1 and 500 characters.');
        }
        $this->status = StockTransferStatus::Cancelled;
        $this->cancellationReason = $reason;
        $this->cancelledBy = $actorId;
        $this->cancelledAt = $at->setTimezone(new DateTimeZone('UTC'));
        ++$this->version;
    }

    /** @param array<string, \Zandu\SharedKernel\Quantity\Quantity> $shippedQuantities keyed by StockTransferLineId */
    public function ship(ActorId $actorId, DateTimeImmutable $at, array $shippedQuantities): void
    {
        $this->ensureDraft();
        if ([] === $this->lines) {
            throw InventoryRuleViolation::with('STOCK_TRANSFER_EMPTY', 'A stock transfer must contain at least one line before shipment.');
        }
        $shippedLines = [];
        foreach ($this->lines as $line) {
            $key = $line->id()->toString();
            $quantity = $shippedQuantities[$key] ?? throw InventoryRuleViolation::with('TRANSFER_SHIPPED_QUANTITY_REQUIRED', 'Every stock transfer line requires a shipped quantity.');
            $shippedLines[] = $line->withShippedQuantity($quantity);
            unset($shippedQuantities[$key]);
        }
        if ([] !== $shippedQuantities) {
            throw InventoryRuleViolation::with('TRANSFER_SHIPPED_LINE_UNKNOWN', 'Shipped quantities contain an unknown stock transfer line.');
        }
        $this->lines = $shippedLines;
        $this->status = StockTransferStatus::Shipped;
        $this->shippedBy = $actorId;
        $this->shippedAt = $at->setTimezone(new DateTimeZone('UTC'));
        ++$this->version;
    }

    private function ensureDraft(): void
    {
        if (StockTransferStatus::Draft !== $this->status) {
            throw InventoryRuleViolation::with('STOCK_TRANSFER_NOT_EDITABLE', 'Only a draft stock transfer can be edited.');
        }
    }

    public function id(): StockTransferId
    {
        return $this->id;
    }
    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }
    public function sourceStoreId(): StoreId
    {
        return $this->sourceStoreId;
    }
    public function destinationStoreId(): StoreId
    {
        return $this->destinationStoreId;
    }
    public function status(): StockTransferStatus
    {
        return $this->status;
    }
    public function createdBy(): ActorId
    {
        return $this->createdBy;
    }
    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function shippedBy(): ?ActorId
    {
        return $this->shippedBy;
    }
    public function shippedAt(): ?DateTimeImmutable
    {
        return $this->shippedAt;
    }
    public function cancellationReason(): ?string
    {
        return $this->cancellationReason;
    }
    public function cancelledBy(): ?ActorId
    {
        return $this->cancelledBy;
    }
    public function cancelledAt(): ?DateTimeImmutable
    {
        return $this->cancelledAt;
    }
    public function version(): int
    {
        return $this->version;
    }
    /** @return list<StockTransferLine> */ public function lines(): array
    {
        return $this->lines;
    }
}
