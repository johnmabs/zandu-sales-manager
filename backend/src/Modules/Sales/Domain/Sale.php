<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Domain;

use DateTimeImmutable;
use LogicException;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\{SaleId,StoreId};
use Zandu\SharedKernel\Money\Money;

final class Sale
{
    private function __construct(
        private readonly SaleId $id,
        private readonly string $organizationId,
        private readonly StoreId $storeId,
        private SaleStatus $status,
        private readonly string $currency,
        private Money $subtotal,
        private Money $discountTotal,
        private Money $taxTotal,
        private Money $total,
        private readonly ActorContext $createdBy,
        private readonly DateTimeImmutable $createdAt,
        private ?ActorContext $completedBy = null,
        private ?DateTimeImmutable $completedAt = null,
        private ?ActorContext $cancelledBy = null,
        private ?DateTimeImmutable $cancelledAt = null,
        private int $version = 1,
        private int $lineCount = 0,
    ) {}

    public static function create(SaleId $id, string $organizationId, StoreId $storeId, string $currency, Money $zero, ActorContext $actor, DateTimeImmutable $at): self
    {
        return new self($id, $organizationId, $storeId, SaleStatus::Draft, $currency, $zero, $zero, $zero, $zero, $actor, $at);
    }

    public function addLine(): void
    {
        $this->ensureEditable();
        ++$this->lineCount;
        ++$this->version;
    }

    public function complete(ActorContext $actor, DateTimeImmutable $at): void
    {
        $this->ensureEditable();
        if (0 === $this->lineCount) {
            throw new LogicException('A sale must contain at least one line.');
        }
        $this->status = SaleStatus::Completed;
        $this->completedBy = $actor;
        $this->completedAt = $at;
        ++$this->version;
    }

    public function cancel(ActorContext $actor, DateTimeImmutable $at): void
    {
        if (SaleStatus::Draft !== $this->status && SaleStatus::AwaitingPayment !== $this->status) {
            throw new LogicException('Only an open sale can be cancelled.');
        }
        $this->status = SaleStatus::Cancelled;
        $this->cancelledBy = $actor;
        $this->cancelledAt = $at;
        ++$this->version;
    }

    private function ensureEditable(): void
    {
        if (SaleStatus::Draft !== $this->status && SaleStatus::AwaitingPayment !== $this->status) {
            throw new LogicException('The sale is not editable.');
        }
    }

    public function id(): SaleId
    {
        return $this->id;
    }
    public function organizationId(): string
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
    public function version(): int
    {
        return $this->version;
    }
    public function lineCount(): int
    {
        return $this->lineCount;
    }
    public function createdBy(): ActorContext
    {
        return $this->createdBy;
    }
    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function completedBy(): ?ActorContext
    {
        return $this->completedBy;
    }
    public function completedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }
    public function cancelledBy(): ?ActorContext
    {
        return $this->cancelledBy;
    }
    public function cancelledAt(): ?DateTimeImmutable
    {
        return $this->cancelledAt;
    }
}
