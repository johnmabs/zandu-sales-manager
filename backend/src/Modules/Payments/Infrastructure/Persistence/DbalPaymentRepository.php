<?php

declare(strict_types=1);

namespace Zandu\Modules\Payments\Infrastructure\Persistence;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Zandu\Modules\Payments\Domain\{Payment, PaymentRepository, PaymentRuleViolation, PaymentStatus};
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Identity\{ActorId,OrganizationId,PaymentId,SaleId,UuidFactory};
use Zandu\SharedKernel\Money\{Currency,Money};

final readonly class DbalPaymentRepository implements PaymentRepository
{
    public function __construct(private Connection $connection, private UuidFactory $uuids, private DecimalFactory $decimals) {}

    public function addConfirmedOnce(Payment $payment): bool
    {
        $affected = $this->connection->executeStatement(
            "INSERT INTO payments.payment (id, organization_id, purpose, target_reference, method, status, amount, currency, created_by, created_at, confirmed_at, version) VALUES (?, ?, 'SALE', ?, 'CASH', ?, ?, ?, ?, ?, ?, ?) ON CONFLICT (organization_id, target_reference, method) WHERE status <> 'CANCELLED' DO NOTHING",
            [
                $payment->id()->toString(), $payment->organizationId()->toString(), $payment->targetReference()->toString(), $payment->status()->value,
                $payment->amount()->amount()->toString(), $payment->amount()->currency()->code(), $payment->createdBy()->toString(), $payment->createdAt()->format(DATE_ATOM),
                $payment->confirmedAt()?->format(DATE_ATOM), $payment->version(),
            ],
        );

        return 1 === $affected;
    }

    public function findConfirmedCashSale(OrganizationId $organizationId, SaleId $saleId): ?Payment
    {
        $row = $this->connection->fetchAssociative(
            "SELECT * FROM payments.payment WHERE organization_id = ? AND purpose = 'SALE' AND target_reference = ? AND method = 'CASH' AND status = 'CONFIRMED'",
            [$organizationId->toString(), $saleId->toString()],
        );
        if (false === $row) {
            return null;
        }
        return $this->payment($row, $organizationId);
    }

    public function getForUpdate(OrganizationId $organizationId, PaymentId $paymentId): Payment
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM payments.payment WHERE organization_id = ? AND id = ? FOR UPDATE',
            [$organizationId->toString(), $paymentId->toString()],
        );
        if (false === $row) {
            throw PaymentRuleViolation::with('PAYMENT_NOT_FOUND', 'Payment not found.');
        }

        return $this->payment($row, $organizationId);
    }

    /** @param array<string, mixed> $row */
    private function payment(array $row, OrganizationId $organizationId): Payment
    {
        $currency = Currency::fromCode((string) $row['currency']);

        return Payment::reconstitute(
            PaymentId::fromString((string) $row['id'], $this->uuids),
            $organizationId,
            SaleId::fromString((string) $row['target_reference'], $this->uuids),
            Money::fromString((string) $row['amount'], $currency, $this->decimals),
            PaymentStatus::from((string) $row['status']),
            ActorId::fromString((string) $row['created_by'], $this->uuids),
            new DateTimeImmutable((string) $row['created_at']),
            null === $row['confirmed_at'] ? null : new DateTimeImmutable((string) $row['confirmed_at']),
            (int) $row['version'],
        );
    }
}
