<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Domain\GoodsReceipt;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\Event\GoodsReceiptCancelled;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\Event\GoodsReceiptCreated;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\Event\GoodsReceiptEvent;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\Event\GoodsReceiptPosted;
use Zandu\Modules\Purchasing\Domain\PurchasingRuleViolation;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\GoodsReceiptId;
use Zandu\SharedKernel\Identity\GoodsReceiptLineId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\PurchaseOrderId;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Identity\SupplierId;

final class GoodsReceipt
{
    /** @var list<GoodsReceiptEvent> */
    private array $recordedEvents = [];

    /** @param list<GoodsReceiptLine> $lines */
    private function __construct(
        private readonly GoodsReceiptId $id,
        private readonly OrganizationId $organizationId,
        private readonly StoreId $storeId,
        private readonly SupplierId $supplierId,
        private readonly ?PurchaseOrderId $purchaseOrderId,
        private readonly GoodsReceiptNumber $number,
        private GoodsReceiptStatus $status,
        private ?string $supplierDeliveryNote,
        private ?string $notes,
        private readonly ActorId $createdBy,
        private readonly DateTimeImmutable $createdAt,
        private ?ActorId $postedBy,
        private ?DateTimeImmutable $postedAt,
        private ?ActorId $cancelledBy,
        private ?DateTimeImmutable $cancelledAt,
        private int $version,
        private array $lines,
    ) {
        if ($version < 1) {
            throw new InvalidArgumentException('Goods receipt version must be positive.');
        }
        $this->supplierDeliveryNote = self::optional($supplierDeliveryNote, 128, 'supplier delivery note');
        $this->notes = self::optional($notes, 2000, 'notes');
    }

    public static function create(
        GoodsReceiptId $id,
        OrganizationId $organizationId,
        StoreId $storeId,
        SupplierId $supplierId,
        ?PurchaseOrderId $purchaseOrderId,
        GoodsReceiptNumber $number,
        ?string $supplierDeliveryNote,
        ?string $notes,
        ActorId $createdBy,
        DateTimeImmutable $createdAt,
    ): self {
        $createdAt = self::utc($createdAt);
        $receipt = new self($id, $organizationId, $storeId, $supplierId, $purchaseOrderId, $number, GoodsReceiptStatus::Draft, $supplierDeliveryNote, $notes, $createdBy, $createdAt, null, null, null, null, 1, []);
        $receipt->recordedEvents[] = new GoodsReceiptCreated($organizationId, $id, $createdBy, $createdAt);

        return $receipt;
    }

    /** @param list<GoodsReceiptLine> $lines */
    public static function reconstitute(
        GoodsReceiptId $id,
        OrganizationId $organizationId,
        StoreId $storeId,
        SupplierId $supplierId,
        ?PurchaseOrderId $purchaseOrderId,
        GoodsReceiptNumber $number,
        GoodsReceiptStatus $status,
        ?string $supplierDeliveryNote,
        ?string $notes,
        ActorId $createdBy,
        DateTimeImmutable $createdAt,
        ?ActorId $postedBy,
        ?DateTimeImmutable $postedAt,
        ?ActorId $cancelledBy,
        ?DateTimeImmutable $cancelledAt,
        int $version,
        array $lines,
    ): self {
        return new self($id, $organizationId, $storeId, $supplierId, $purchaseOrderId, $number, $status, $supplierDeliveryNote, $notes, $createdBy, self::utc($createdAt), $postedBy, self::utcOrNull($postedAt), $cancelledBy, self::utcOrNull($cancelledAt), $version, $lines);
    }

    public function addLine(GoodsReceiptLine $line): void
    {
        $this->ensureDraft();
        $this->assertLine($line, null);
        $this->lines[] = $line;
        ++$this->version;
    }

    public function updateLine(GoodsReceiptLine $replacement): void
    {
        $this->ensureDraft();
        foreach ($this->lines as $index => $line) {
            if ($line->id()->equals($replacement->id())) {
                $this->assertLine($replacement, $replacement->id());
                $this->lines[$index] = $replacement;
                ++$this->version;

                return;
            }
        }
        throw PurchasingRuleViolation::with('GOODS_RECEIPT_LINE_NOT_FOUND', 'Goods receipt line not found.');
    }

    public function removeLine(GoodsReceiptLineId $lineId): void
    {
        $this->ensureDraft();
        foreach ($this->lines as $index => $line) {
            if ($line->id()->equals($lineId)) {
                array_splice($this->lines, $index, 1);
                ++$this->version;

                return;
            }
        }
        throw PurchasingRuleViolation::with('GOODS_RECEIPT_LINE_NOT_FOUND', 'Goods receipt line not found.');
    }

    public function post(ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        $this->ensureDraft();
        if ([] === $this->lines) {
            throw PurchasingRuleViolation::with('GOODS_RECEIPT_EMPTY', 'A goods receipt must contain at least one line before posting.');
        }
        $occurredAt = self::utc($occurredAt);
        $this->status = GoodsReceiptStatus::Posted;
        $this->postedBy = $actorId;
        $this->postedAt = $occurredAt;
        ++$this->version;
        $this->recordedEvents[] = new GoodsReceiptPosted($this->organizationId, $this->id, $actorId, $occurredAt);
    }

    public function cancel(ActorId $actorId, DateTimeImmutable $occurredAt): void
    {
        $this->ensureDraft();
        $occurredAt = self::utc($occurredAt);
        $this->status = GoodsReceiptStatus::Cancelled;
        $this->cancelledBy = $actorId;
        $this->cancelledAt = $occurredAt;
        ++$this->version;
        $this->recordedEvents[] = new GoodsReceiptCancelled($this->organizationId, $this->id, $actorId, $occurredAt);
    }

    /** @return list<GoodsReceiptEvent> */
    public function releaseEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }

    private function assertLine(GoodsReceiptLine $line, ?GoodsReceiptLineId $ignoredLineId): void
    {
        if (!$line->goodsReceiptId()->equals($this->id)) {
            throw PurchasingRuleViolation::with('GOODS_RECEIPT_LINE_MISMATCH', 'Line belongs to another goods receipt.');
        }
        if ((null === $this->purchaseOrderId) !== (null === $line->purchaseOrderLineId())) {
            throw PurchasingRuleViolation::with('GOODS_RECEIPT_PURCHASE_ORDER_LINE_INVALID', 'Linked receipts require linked lines and direct receipts forbid them.');
        }
        foreach ($this->lines as $existing) {
            if ((null === $ignoredLineId || !$existing->id()->equals($ignoredLineId)) && $existing->productId()->equals($line->productId())) {
                throw PurchasingRuleViolation::with('GOODS_RECEIPT_PRODUCT_DUPLICATE', 'A product can occur only once in a goods receipt.');
            }
            if (!$existing->inventoryUnitCost()->currency()->equals($line->inventoryUnitCost()->currency())) {
                throw PurchasingRuleViolation::with('GOODS_RECEIPT_CURRENCY_MISMATCH', 'Every receipt line must use the same currency.');
            }
        }
    }

    private function ensureDraft(): void
    {
        if (GoodsReceiptStatus::Draft !== $this->status) {
            throw PurchasingRuleViolation::with(GoodsReceiptStatus::Posted === $this->status ? 'GOODS_RECEIPT_ALREADY_POSTED' : 'GOODS_RECEIPT_NOT_EDITABLE', 'Only a draft goods receipt can be changed.');
        }
    }

    public function id(): GoodsReceiptId
    {
        return $this->id;
    }
    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }
    public function storeId(): StoreId
    {
        return $this->storeId;
    }
    public function supplierId(): SupplierId
    {
        return $this->supplierId;
    }
    public function purchaseOrderId(): ?PurchaseOrderId
    {
        return $this->purchaseOrderId;
    }
    public function number(): GoodsReceiptNumber
    {
        return $this->number;
    }
    public function status(): GoodsReceiptStatus
    {
        return $this->status;
    }
    public function supplierDeliveryNote(): ?string
    {
        return $this->supplierDeliveryNote;
    }
    public function notes(): ?string
    {
        return $this->notes;
    }
    public function createdBy(): ActorId
    {
        return $this->createdBy;
    }
    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function postedBy(): ?ActorId
    {
        return $this->postedBy;
    }
    public function postedAt(): ?DateTimeImmutable
    {
        return $this->postedAt;
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
    /** @return list<GoodsReceiptLine> */ public function lines(): array
    {
        return $this->lines;
    }
    public function lineCount(): int
    {
        return count($this->lines);
    }

    private static function optional(?string $value, int $maxLength, string $field): ?string
    {
        if (null === $value || '' === trim($value)) {
            return null;
        }
        $value = trim($value);
        if (mb_strlen($value) > $maxLength) {
            throw new InvalidArgumentException(sprintf('Goods receipt %s cannot exceed %d characters.', $field, $maxLength));
        }

        return $value;
    }

    private static function utc(DateTimeImmutable $at): DateTimeImmutable
    {
        return $at->setTimezone(new DateTimeZone('UTC'));
    }
    private static function utcOrNull(?DateTimeImmutable $at): ?DateTimeImmutable
    {
        return null !== $at ? self::utc($at) : null;
    }
}
