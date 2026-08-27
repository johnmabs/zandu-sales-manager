<?php

declare(strict_types=1);

namespace Zandu\Modules\Payments\Domain;

use DateTimeImmutable;
use LogicException;
use Zandu\SharedKernel\Identity\{ActorId,OrganizationId,PaymentId,SaleId};
use Zandu\SharedKernel\Money\Money;

final class Payment
{
    private function __construct(private readonly PaymentId $id, private readonly OrganizationId $organizationId, private readonly SaleId $targetReference, private readonly Money $amount, private PaymentStatus $status, private readonly ActorId $createdBy, private readonly DateTimeImmutable $createdAt, private ?DateTimeImmutable $confirmedAt = null, private int $version = 1) {}

    public static function createCashSale(PaymentId $id, OrganizationId $organizationId, SaleId $saleId, Money $amount, ActorId $actor, DateTimeImmutable $at): self
    {
        if ($amount->amount()->isNegative() || $amount->amount()->isZero()) {
            throw new \InvalidArgumentException('Payment amount must be positive.');
        }
        return new self($id, $organizationId, $saleId, $amount, PaymentStatus::Created, $actor, $at);
    }

    public static function reconstitute(PaymentId $id, OrganizationId $organizationId, SaleId $saleId, Money $amount, PaymentStatus $status, ActorId $actor, DateTimeImmutable $createdAt, ?DateTimeImmutable $confirmedAt, int $version): self
    {
        return new self($id, $organizationId, $saleId, $amount, $status, $actor, $createdAt, $confirmedAt, $version);
    }

    public function confirm(DateTimeImmutable $at): void
    {
        if (PaymentStatus::Created !== $this->status) {
            throw new LogicException('Only a created payment can be confirmed.');
        }
        $this->status = PaymentStatus::Confirmed;
        $this->confirmedAt = $at;
        ++$this->version;
    }

    public function id(): PaymentId
    {
        return $this->id;
    }
    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }
    public function targetReference(): SaleId
    {
        return $this->targetReference;
    }
    public function purpose(): string
    {
        return 'SALE';
    }
    public function method(): string
    {
        return 'CASH';
    }
    public function amount(): Money
    {
        return $this->amount;
    }
    public function status(): PaymentStatus
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
    public function confirmedAt(): ?DateTimeImmutable
    {
        return $this->confirmedAt;
    }
    public function version(): int
    {
        return $this->version;
    }
}
