<?php

declare(strict_types=1);

namespace Zandu\Modules\Payments\Infrastructure\Persistence;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Zandu\Modules\Payments\Domain\{PaymentRefund, PaymentRefundRepository, PaymentRefundStatus};
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Idempotency\IdempotencyKey;
use Zandu\SharedKernel\Identity\{ActorId, CashSessionId, OrganizationId, PaymentId, PaymentRefundId, ReturnSaleId, UuidFactory};
use Zandu\SharedKernel\Money\{Currency, Money};

final readonly class DbalPaymentRefundRepository implements PaymentRefundRepository
{
    public function __construct(
        private Connection $connection,
        private UuidFactory $uuids,
        private DecimalFactory $decimals,
    ) {}

    public function add(PaymentRefund $refund): void
    {
        $this->connection->insert('payments.payment_refund', [
            'id' => $refund->id()->toString(),
            'organization_id' => $refund->organizationId()->toString(),
            'payment_id' => $refund->paymentId()->toString(),
            'return_sale_id' => $refund->returnSaleId()->toString(),
            'cash_session_id' => $refund->cashSessionId()->toString(),
            'status' => $refund->status()->value,
            'amount' => $refund->amount()->amount()->toString(),
            'currency' => $refund->amount()->currency()->code(),
            'reason' => $refund->reason(),
            'idempotency_key' => $refund->idempotencyKey()->toString(),
            'payload_hash' => $refund->payloadHash(),
            'created_by' => $refund->createdBy()->toString(),
            'created_at' => $refund->createdAt()->format(DATE_ATOM),
            'confirmed_at' => $refund->confirmedAt()?->format(DATE_ATOM),
            'version' => $refund->version(),
        ]);
    }

    public function findByIdempotencyKey(OrganizationId $organizationId, PaymentId $paymentId, IdempotencyKey $key): ?PaymentRefund
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM payments.payment_refund WHERE organization_id = ? AND payment_id = ? AND idempotency_key = ?',
            [$organizationId->toString(), $paymentId->toString(), $key->toString()],
        );

        return false === $row ? null : $this->refund($row, $organizationId);
    }

    public function confirmedTotalForPayment(OrganizationId $organizationId, PaymentId $paymentId, Currency $currency): Money
    {
        return $this->total('payment_id', $organizationId, $paymentId->toString(), $currency);
    }

    public function confirmedTotalForReturn(OrganizationId $organizationId, ReturnSaleId $returnSaleId, Currency $currency): Money
    {
        return $this->total('return_sale_id', $organizationId, $returnSaleId->toString(), $currency);
    }

    private function total(string $referenceColumn, OrganizationId $organizationId, string $referenceId, Currency $currency): Money
    {
        $amount = $this->connection->fetchOne(
            "SELECT COALESCE(SUM(amount), 0) FROM payments.payment_refund WHERE organization_id = ? AND $referenceColumn = ? AND status = 'CONFIRMED' AND currency = ?",
            [$organizationId->toString(), $referenceId, $currency->code()],
        );

        return Money::fromString((string) $amount, $currency, $this->decimals);
    }

    /** @param array<string, mixed> $row */
    private function refund(array $row, OrganizationId $organizationId): PaymentRefund
    {
        $currency = Currency::fromCode((string) $row['currency']);

        return PaymentRefund::reconstitute(
            PaymentRefundId::fromString((string) $row['id'], $this->uuids),
            $organizationId,
            PaymentId::fromString((string) $row['payment_id'], $this->uuids),
            ReturnSaleId::fromString((string) $row['return_sale_id'], $this->uuids),
            CashSessionId::fromString((string) $row['cash_session_id'], $this->uuids),
            Money::fromString((string) $row['amount'], $currency, $this->decimals),
            null === $row['reason'] ? null : (string) $row['reason'],
            IdempotencyKey::fromString((string) $row['idempotency_key']),
            (string) $row['payload_hash'],
            PaymentRefundStatus::from((string) $row['status']),
            ActorId::fromString((string) $row['created_by'], $this->uuids),
            new DateTimeImmutable((string) $row['created_at']),
            null === $row['confirmed_at'] ? null : new DateTimeImmutable((string) $row['confirmed_at']),
            (int) $row['version'],
        );
    }
}
