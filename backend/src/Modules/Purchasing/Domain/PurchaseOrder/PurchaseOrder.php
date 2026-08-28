<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Domain\PurchaseOrder;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Zandu\Modules\Purchasing\Domain\PurchasingRuleViolation;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\PurchaseOrderId;
use Zandu\SharedKernel\Identity\PurchaseOrderLineId;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Identity\SupplierId;
use Zandu\SharedKernel\Money\Currency;
use Zandu\SharedKernel\Money\Money;

final class PurchaseOrder
{
    /** @param list<PurchaseOrderLine> $lines */
    private function __construct(
        private readonly PurchaseOrderId $id,
        private readonly OrganizationId $organizationId,
        private readonly StoreId $destinationStoreId,
        private readonly SupplierId $supplierId,
        private readonly PurchaseOrderNumber $number,
        private PurchaseOrderStatus $status,
        private readonly Currency $currency,
        private Money $expectedTotal,
        private readonly ActorId $createdBy,
        private readonly DateTimeImmutable $createdAt,
        private ?ActorId $confirmedBy,
        private ?DateTimeImmutable $confirmedAt,
        private ?ActorId $closedBy,
        private ?DateTimeImmutable $closedAt,
        private ?string $closedReason,
        private ?ActorId $cancelledBy,
        private ?DateTimeImmutable $cancelledAt,
        private int $version,
        private array $lines,
    ) {
        if ($version < 1) {
            throw new InvalidArgumentException('Purchase order version must be positive.');
        }
        if (!$expectedTotal->currency()->equals($currency) || $expectedTotal->amount()->isNegative()) {
            throw new InvalidArgumentException('Purchase order total must be non-negative and use its currency.');
        }
    }

    public static function create(
        PurchaseOrderId $id,
        OrganizationId $organizationId,
        StoreId $destinationStoreId,
        SupplierId $supplierId,
        PurchaseOrderNumber $number,
        Currency $currency,
        Money $zeroTotal,
        ActorId $createdBy,
        DateTimeImmutable $createdAt,
    ): self {
        if (!$zeroTotal->amount()->isZero()) {
            throw new InvalidArgumentException('A new purchase order total must be zero.');
        }

        return new self($id, $organizationId, $destinationStoreId, $supplierId, $number, PurchaseOrderStatus::Draft, $currency, $zeroTotal, $createdBy, self::utc($createdAt), null, null, null, null, null, null, null, 1, []);
    }

    /** @param list<PurchaseOrderLine> $lines */
    public static function reconstitute(
        PurchaseOrderId $id,
        OrganizationId $organizationId,
        StoreId $destinationStoreId,
        SupplierId $supplierId,
        PurchaseOrderNumber $number,
        PurchaseOrderStatus $status,
        Currency $currency,
        Money $expectedTotal,
        ActorId $createdBy,
        DateTimeImmutable $createdAt,
        ?ActorId $confirmedBy,
        ?DateTimeImmutable $confirmedAt,
        ?ActorId $closedBy,
        ?DateTimeImmutable $closedAt,
        ?string $closedReason,
        ?ActorId $cancelledBy,
        ?DateTimeImmutable $cancelledAt,
        int $version,
        array $lines,
    ): self {
        return new self($id, $organizationId, $destinationStoreId, $supplierId, $number, $status, $currency, $expectedTotal, $createdBy, self::utc($createdAt), $confirmedBy, self::utcOrNull($confirmedAt), $closedBy, self::utcOrNull($closedAt), $closedReason, $cancelledBy, self::utcOrNull($cancelledAt), $version, $lines);
    }

    public function addLine(PurchaseOrderLine $line): void
    {
        $this->ensureDraft();
        $this->assertLine($line, null);
        $this->lines[] = $line;
        $this->expectedTotal = $this->expectedTotal->add($line->expectedTotal());
        ++$this->version;
    }

    public function updateLine(PurchaseOrderLine $replacement): void
    {
        $this->ensureDraft();
        foreach ($this->lines as $index => $line) {
            if ($line->id()->equals($replacement->id())) {
                $this->assertLine($replacement, $replacement->id());
                $this->expectedTotal = $this->expectedTotal->subtract($line->expectedTotal())->add($replacement->expectedTotal());
                $this->lines[$index] = $replacement;
                ++$this->version;

                return;
            }
        }

        throw PurchasingRuleViolation::with('PURCHASE_ORDER_LINE_NOT_FOUND', 'Purchase order line not found.');
    }

    public function removeLine(PurchaseOrderLineId $lineId): void
    {
        $this->ensureDraft();
        foreach ($this->lines as $index => $line) {
            if ($line->id()->equals($lineId)) {
                $this->expectedTotal = $this->expectedTotal->subtract($line->expectedTotal());
                array_splice($this->lines, $index, 1);
                ++$this->version;

                return;
            }
        }

        throw PurchasingRuleViolation::with('PURCHASE_ORDER_LINE_NOT_FOUND', 'Purchase order line not found.');
    }

    private function assertLine(PurchaseOrderLine $line, ?PurchaseOrderLineId $ignoredLineId): void
    {
        if (!$line->purchaseOrderId()->equals($this->id)) {
            throw PurchasingRuleViolation::with('PURCHASE_ORDER_LINE_MISMATCH', 'Line belongs to another purchase order.');
        }
        if (!$line->unitCost()->currency()->equals($this->currency)) {
            throw PurchasingRuleViolation::with('PURCHASE_ORDER_CURRENCY_MISMATCH', 'Every line must use the purchase order currency.');
        }
        foreach ($this->lines as $existing) {
            if ((null === $ignoredLineId || !$existing->id()->equals($ignoredLineId)) && $existing->productId()->equals($line->productId())) {
                throw PurchasingRuleViolation::with('PURCHASE_ORDER_PRODUCT_DUPLICATE', 'A product can occur only once in a purchase order.');
            }
        }
    }

    private function ensureDraft(): void
    {
        if (PurchaseOrderStatus::Draft !== $this->status) {
            throw PurchasingRuleViolation::with('PURCHASE_ORDER_NOT_EDITABLE', 'Only a draft purchase order can be edited.');
        }
    }

    public function id(): PurchaseOrderId
    {
        return $this->id;
    }
    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }
    public function destinationStoreId(): StoreId
    {
        return $this->destinationStoreId;
    }
    public function supplierId(): SupplierId
    {
        return $this->supplierId;
    }
    public function number(): PurchaseOrderNumber
    {
        return $this->number;
    }
    public function status(): PurchaseOrderStatus
    {
        return $this->status;
    }
    public function currency(): Currency
    {
        return $this->currency;
    }
    public function expectedTotal(): Money
    {
        return $this->expectedTotal;
    }
    public function createdBy(): ActorId
    {
        return $this->createdBy;
    }
    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function confirmedBy(): ?ActorId
    {
        return $this->confirmedBy;
    }
    public function confirmedAt(): ?DateTimeImmutable
    {
        return $this->confirmedAt;
    }
    public function closedBy(): ?ActorId
    {
        return $this->closedBy;
    }
    public function closedAt(): ?DateTimeImmutable
    {
        return $this->closedAt;
    }
    public function closedReason(): ?string
    {
        return $this->closedReason;
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
    /** @return list<PurchaseOrderLine> */
    public function lines(): array
    {
        return $this->lines;
    }
    public function lineCount(): int
    {
        return count($this->lines);
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
