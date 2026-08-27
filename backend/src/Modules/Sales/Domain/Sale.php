<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Domain;

use DateTimeImmutable;
use LogicException;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\{ActorId,OrganizationId,SaleId,SaleLineId,StoreId};
use Zandu\SharedKernel\Money\Money;

final class Sale
{
    private function __construct(
        private readonly SaleId $id,
        private readonly OrganizationId $organizationId,
        private readonly StoreId $storeId,
        private SaleStatus $status,
        private readonly string $currency,
        private Money $subtotal,
        private Money $discountTotal,
        private Money $taxTotal,
        private Money $total,
        private ?string $businessDate,
        private readonly ActorId $createdBy,
        private readonly DateTimeImmutable $createdAt,
        private ?ActorId $completedBy = null,
        private ?DateTimeImmutable $completedAt = null,
        private ?ActorId $cancelledBy = null,
        private ?DateTimeImmutable $cancelledAt = null,
        private int $version = 1,
        /** @var list<SaleLine> */
        private array $lines = [],
    ) {}

    public static function create(SaleId $id, OrganizationId $organizationId, StoreId $storeId, string $currency, Money $zero, ActorContext $actor, DateTimeImmutable $at): self
    {
        if (!$actor->organizationId()->equals($organizationId)) {
            throw new LogicException('Actor organization does not match sale organization.');
        }
        if ($zero->currency()->code() !== $currency || !$zero->amount()->isZero()) {
            throw new LogicException('Sale must be initialized with zero money in its currency.');
        }
        return new self($id, $organizationId, $storeId, SaleStatus::Draft, $currency, $zero, $zero, $zero, $zero, null, $actor->actorId(), $at);
    }

    /** @param list<SaleLine> $lines */
    public static function reconstitute(SaleId $id, OrganizationId $organizationId, StoreId $storeId, SaleStatus $status, string $currency, Money $subtotal, Money $discountTotal, Money $taxTotal, Money $total, ?string $businessDate, ActorId $createdBy, DateTimeImmutable $createdAt, ?ActorId $completedBy, ?DateTimeImmutable $completedAt, ?ActorId $cancelledBy, ?DateTimeImmutable $cancelledAt, int $version, array $lines): self
    {
        return new self($id, $organizationId, $storeId, $status, $currency, $subtotal, $discountTotal, $taxTotal, $total, $businessDate, $createdBy, $createdAt, $completedBy, $completedAt, $cancelledBy, $cancelledAt, $version, $lines);
    }

    public function addLine(SaleLine $line): void
    {
        $this->ensureEditable();
        if (!$line->saleId()->equals($this->id)) {
            throw new LogicException('Sale line belongs to another sale.');
        }
        $this->lines[] = $line;
        $this->addLineTotals($line);
        ++$this->version;
    }

    public function replaceLine(SaleLine $replacement): void
    {
        $this->ensureEditable();
        foreach ($this->lines as $index => $line) {
            if ($line->id()->equals($replacement->id())) {
                if (!$replacement->saleId()->equals($this->id)) {
                    throw new LogicException('Sale line belongs to another sale.');
                }
                $this->subtractLineTotals($line);
                $this->lines[$index] = $replacement;
                $this->addLineTotals($replacement);
                ++$this->version;

                return;
            }
        }
        throw new LogicException('Sale line not found.');
    }

    public function removeLine(SaleLineId $lineId): void
    {
        $this->ensureEditable();
        foreach ($this->lines as $index => $line) {
            if ($line->id()->equals($lineId)) {
                $this->subtractLineTotals($line);
                array_splice($this->lines, $index, 1);
                ++$this->version;

                return;
            }
        }
        throw new LogicException('Sale line not found.');
    }

    public function line(SaleLineId $lineId): SaleLine
    {
        foreach ($this->lines as $line) {
            if ($line->id()->equals($lineId)) {
                return $line;
            }
        }
        throw new LogicException('Sale line not found.');
    }

    private function addLineTotals(SaleLine $line): void
    {
        $this->subtotal = $this->subtotal->add($line->subtotal());
        $this->discountTotal = $this->discountTotal->add($line->discountAmount());
        $this->taxTotal = $this->taxTotal->add($line->taxAmount());
        $this->total = $this->total->add($line->total());
    }

    private function subtractLineTotals(SaleLine $line): void
    {
        $this->subtotal = $this->subtotal->subtract($line->subtotal());
        $this->discountTotal = $this->discountTotal->subtract($line->discountAmount());
        $this->taxTotal = $this->taxTotal->subtract($line->taxAmount());
        $this->total = $this->total->subtract($line->total());
    }

    public function complete(ActorContext $actor, DateTimeImmutable $at, string $businessDate): void
    {
        $this->ensureEditable();
        if ([] === $this->lines) {
            throw SalesRuleViolation::with('SALE_EMPTY', 'A sale must contain at least one line.');
        }
        if (!$actor->organizationId()->equals($this->organizationId)) {
            throw new LogicException('Actor organization does not match sale organization.');
        }
        $parsedBusinessDate = DateTimeImmutable::createFromFormat('!Y-m-d', $businessDate);
        if (false === $parsedBusinessDate || $parsedBusinessDate->format('Y-m-d') !== $businessDate) {
            throw new LogicException('Business date must use the Y-m-d format.');
        }
        $this->status = SaleStatus::Completed;
        $this->businessDate = $businessDate;
        $this->completedBy = $actor->actorId();
        $this->completedAt = $at;
        ++$this->version;
    }

    public function cancel(ActorContext $actor, DateTimeImmutable $at): void
    {
        if (SaleStatus::Draft !== $this->status && SaleStatus::AwaitingPayment !== $this->status) {
            throw SalesRuleViolation::with(SaleStatus::Completed === $this->status ? 'SALE_ALREADY_COMPLETED' : 'SALE_CANCELLED', 'Only an open sale can be cancelled.');
        }
        if (!$actor->organizationId()->equals($this->organizationId)) {
            throw new LogicException('Actor organization does not match sale organization.');
        }
        $this->status = SaleStatus::Cancelled;
        $this->cancelledBy = $actor->actorId();
        $this->cancelledAt = $at;
        ++$this->version;
    }

    private function ensureEditable(): void
    {
        if (SaleStatus::Draft !== $this->status && SaleStatus::AwaitingPayment !== $this->status) {
            throw SalesRuleViolation::with('SALE_NOT_EDITABLE', 'The sale is not editable.');
        }
    }

    public function id(): SaleId
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
    public function status(): SaleStatus
    {
        return $this->status;
    }
    public function currency(): string
    {
        return $this->currency;
    }
    public function subtotal(): Money
    {
        return $this->subtotal;
    }
    public function discountTotal(): Money
    {
        return $this->discountTotal;
    }
    public function taxTotal(): Money
    {
        return $this->taxTotal;
    }
    public function total(): Money
    {
        return $this->total;
    }
    public function businessDate(): ?string
    {
        return $this->businessDate;
    }
    public function version(): int
    {
        return $this->version;
    }
    public function lineCount(): int
    {
        return count($this->lines);
    }
    /** @return list<SaleLine> */ public function lines(): array
    {
        return $this->lines;
    }
    public function createdBy(): ActorId
    {
        return $this->createdBy;
    }
    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function completedBy(): ?ActorId
    {
        return $this->completedBy;
    }
    public function completedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }
    public function cancelledBy(): ?ActorId
    {
        return $this->cancelledBy;
    }
    public function cancelledAt(): ?DateTimeImmutable
    {
        return $this->cancelledAt;
    }
}
