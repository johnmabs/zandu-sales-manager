<?php

declare(strict_types=1);

namespace Zandu\Modules\Payments\Domain;

use DateTimeImmutable;
use LogicException;
use Zandu\SharedKernel\Idempotency\IdempotencyKey;
use Zandu\SharedKernel\Identity\{ActorId, CashSessionId, OrganizationId, PaymentId, PaymentRefundId, ReturnSaleId};
use Zandu\SharedKernel\Money\Money;

final class PaymentRefund
{
    private function __construct(
        private readonly PaymentRefundId $id,
        private readonly OrganizationId $organizationId,
        private readonly PaymentId $paymentId,
        private readonly ReturnSaleId $returnSaleId,
        private readonly CashSessionId $cashSessionId,
        private readonly Money $amount,
        private readonly ?string $reason,
        private readonly IdempotencyKey $idempotencyKey,
        private readonly string $payloadHash,
        private PaymentRefundStatus $status,
        private readonly ActorId $createdBy,
        private readonly DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $confirmedAt = null,
        private int $version = 1,
    ) {
        if ($amount->amount()->isNegative() || $amount->amount()->isZero()) {
            throw PaymentRuleViolation::with('REFUND_AMOUNT_INVALID', 'Refund amount must be greater than zero.');
        }
        if (!preg_match('/^[a-f0-9]{64}$/', $payloadHash)) {
            throw new LogicException('Refund payload hash must be a SHA-256 hexadecimal value.');
        }
    }

    public static function create(
        PaymentRefundId $id,
        OrganizationId $organizationId,
        PaymentId $paymentId,
        ReturnSaleId $returnSaleId,
        CashSessionId $cashSessionId,
        Money $amount,
        ?string $reason,
        IdempotencyKey $idempotencyKey,
        string $payloadHash,
        ActorId $createdBy,
        DateTimeImmutable $createdAt,
    ): self {
        return new self($id, $organizationId, $paymentId, $returnSaleId, $cashSessionId, $amount, self::normalizeReason($reason), $idempotencyKey, $payloadHash, PaymentRefundStatus::Created, $createdBy, $createdAt);
    }

    public static function reconstitute(
        PaymentRefundId $id,
        OrganizationId $organizationId,
        PaymentId $paymentId,
        ReturnSaleId $returnSaleId,
        CashSessionId $cashSessionId,
        Money $amount,
        ?string $reason,
        IdempotencyKey $idempotencyKey,
        string $payloadHash,
        PaymentRefundStatus $status,
        ActorId $createdBy,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $confirmedAt,
        int $version,
    ): self {
        return new self($id, $organizationId, $paymentId, $returnSaleId, $cashSessionId, $amount, $reason, $idempotencyKey, $payloadHash, $status, $createdBy, $createdAt, $confirmedAt, $version);
    }

    public function confirm(DateTimeImmutable $at): void
    {
        if (PaymentRefundStatus::Created !== $this->status) {
            throw PaymentRuleViolation::with('REFUND_ALREADY_CONFIRMED', 'Refund is already confirmed.');
        }
        $this->status = PaymentRefundStatus::Confirmed;
        $this->confirmedAt = $at;
        ++$this->version;
    }

    public function matchesPayload(string $payloadHash): bool
    {
        return hash_equals($this->payloadHash, $payloadHash);
    }

    public function id(): PaymentRefundId
    {
        return $this->id;
    }
    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }
    public function paymentId(): PaymentId
    {
        return $this->paymentId;
    }
    public function returnSaleId(): ReturnSaleId
    {
        return $this->returnSaleId;
    }
    public function cashSessionId(): CashSessionId
    {
        return $this->cashSessionId;
    }
    public function amount(): Money
    {
        return $this->amount;
    }
    public function reason(): ?string
    {
        return $this->reason;
    }
    public function idempotencyKey(): IdempotencyKey
    {
        return $this->idempotencyKey;
    }
    public function payloadHash(): string
    {
        return $this->payloadHash;
    }
    public function status(): PaymentRefundStatus
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

    private static function normalizeReason(?string $reason): ?string
    {
        if (null === $reason || '' === trim($reason)) {
            return null;
        }
        $reason = trim($reason);
        if (mb_strlen($reason) > 500) {
            throw new LogicException('Refund reason cannot exceed 500 characters.');
        }

        return $reason;
    }
}
