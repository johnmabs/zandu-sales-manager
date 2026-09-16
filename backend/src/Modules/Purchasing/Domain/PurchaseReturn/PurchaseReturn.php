<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Domain\PurchaseReturn;

use DateTimeImmutable;
use DateTimeZone;
use Zandu\Modules\Purchasing\Domain\PurchasingRuleViolation;
use Zandu\SharedKernel\Identity\{ActorId, GoodsReceiptId, OrganizationId, PurchaseOrderId, PurchaseReturnId, StoreId, SupplierId};
use Zandu\SharedKernel\Versioning\{TracksAggregateVersion, VersionedAggregate};

final class PurchaseReturn implements VersionedAggregate
{
    use TracksAggregateVersion;

    /** @param list<PurchaseReturnLine> $lines */
    private function __construct(private readonly PurchaseReturnId $id, private readonly OrganizationId $organizationId, private readonly StoreId $sourceStoreId, private readonly SupplierId $supplierId, private readonly ?GoodsReceiptId $goodsReceiptId, private readonly ?PurchaseOrderId $purchaseOrderId, private PurchaseReturnStatus $status, private readonly string $reason, private readonly ActorId $createdBy, private readonly DateTimeImmutable $createdAt, private ?ActorId $shippedBy, private ?DateTimeImmutable $shippedAt, private ?ActorId $cancelledBy, private ?DateTimeImmutable $cancelledAt, private int $version, private array $lines)
    {
        $this->assertValidVersion();
    }

    public static function create(PurchaseReturnId $id, OrganizationId $organizationId, StoreId $sourceStoreId, SupplierId $supplierId, ?GoodsReceiptId $goodsReceiptId, ?PurchaseOrderId $purchaseOrderId, string $reason, ActorId $createdBy, DateTimeImmutable $createdAt): self
    {
        $reason = trim($reason);
        if ('' === $reason) {
            throw PurchasingRuleViolation::with('PURCHASE_RETURN_REASON_REQUIRED', 'A purchase return requires a reason.');
        }
        if (mb_strlen($reason) > 500) {
            throw PurchasingRuleViolation::with('PURCHASE_RETURN_REASON_INVALID', 'Purchase return reason cannot exceed 500 characters.');
        }
        if (null === $goodsReceiptId && null !== $purchaseOrderId) {
            throw PurchasingRuleViolation::with('PURCHASE_RETURN_SOURCE_INVALID', 'A purchase order reference requires a source goods receipt.');
        }
        return new self($id, $organizationId, $sourceStoreId, $supplierId, $goodsReceiptId, $purchaseOrderId, PurchaseReturnStatus::Draft, $reason, $createdBy, $createdAt->setTimezone(new DateTimeZone('UTC')), null, null, null, null, 1, []);
    }

    /** @param list<PurchaseReturnLine> $lines */
    public static function reconstitute(PurchaseReturnId $id, OrganizationId $organizationId, StoreId $sourceStoreId, SupplierId $supplierId, ?GoodsReceiptId $goodsReceiptId, ?PurchaseOrderId $purchaseOrderId, PurchaseReturnStatus $status, string $reason, ActorId $createdBy, DateTimeImmutable $createdAt, ?ActorId $shippedBy, ?DateTimeImmutable $shippedAt, ?ActorId $cancelledBy, ?DateTimeImmutable $cancelledAt, int $version, array $lines): self
    {
        return new self($id, $organizationId, $sourceStoreId, $supplierId, $goodsReceiptId, $purchaseOrderId, $status, $reason, $createdBy, $createdAt, $shippedBy, $shippedAt, $cancelledBy, $cancelledAt, $version, $lines);
    }

    public function addLine(PurchaseReturnLine $line): void
    {
        $this->ensureDraft();
        if (!$line->purchaseReturnId()->equals($this->id) || ((null === $this->goodsReceiptId) !== (null === $line->goodsReceiptLineId()))) {
            throw PurchasingRuleViolation::with('PURCHASE_RETURN_LINE_SOURCE_INVALID', 'Return line source must match its purchase return.');
        }
        foreach ($this->lines as $existing) {
            if ($existing->productId()->equals($line->productId())) {
                throw PurchasingRuleViolation::with('PURCHASE_RETURN_PRODUCT_DUPLICATE', 'A product can occur only once in a purchase return.');
            }
        }
        $this->lines[] = $line;
        $this->advanceVersion();
    }

    public function ship(ActorId $actorId, DateTimeImmutable $at): void
    {
        $this->ensureDraft();
        if ([] === $this->lines) {
            throw PurchasingRuleViolation::with('PURCHASE_RETURN_EMPTY', 'A purchase return must contain at least one line.');
        }
        $this->status = PurchaseReturnStatus::Shipped;
        $this->shippedBy = $actorId;
        $this->shippedAt = $at->setTimezone(new DateTimeZone('UTC'));
        $this->advanceVersion();
    }
    public function cancel(ActorId $actorId, DateTimeImmutable $at): void
    {
        $this->ensureDraft();
        $this->status = PurchaseReturnStatus::Cancelled;
        $this->cancelledBy = $actorId;
        $this->cancelledAt = $at->setTimezone(new DateTimeZone('UTC'));
        $this->advanceVersion();
    }
    private function ensureDraft(): void
    {
        if (PurchaseReturnStatus::Draft !== $this->status) {
            throw PurchasingRuleViolation::with('PURCHASE_RETURN_NOT_EDITABLE', 'A shipped or cancelled purchase return is immutable.');
        }
    }

    public function id(): PurchaseReturnId
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
    public function supplierId(): SupplierId
    {
        return $this->supplierId;
    }
    public function goodsReceiptId(): ?GoodsReceiptId
    {
        return $this->goodsReceiptId;
    }
    public function purchaseOrderId(): ?PurchaseOrderId
    {
        return $this->purchaseOrderId;
    }
    public function status(): PurchaseReturnStatus
    {
        return $this->status;
    }
    public function reason(): string
    {
        return $this->reason;
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
    public function cancelledBy(): ?ActorId
    {
        return $this->cancelledBy;
    }
    public function cancelledAt(): ?DateTimeImmutable
    {
        return $this->cancelledAt;
    }
    /** @return list<PurchaseReturnLine> */ public function lines(): array
    {
        return $this->lines;
    }
}
