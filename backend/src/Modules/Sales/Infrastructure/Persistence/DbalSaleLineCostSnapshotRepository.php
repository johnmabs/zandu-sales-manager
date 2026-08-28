<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Infrastructure\Persistence;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Zandu\Modules\Sales\Domain\{SaleLineCostSnapshot, SaleLineCostSnapshotRepository};
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Identity\{OrganizationId, SaleId, SaleLineId, StockId, StockMovementId, UuidFactory};
use Zandu\SharedKernel\Money\{Currency, Money};
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class DbalSaleLineCostSnapshotRepository implements SaleLineCostSnapshotRepository
{
    public function __construct(
        private Connection $connection,
        private UuidFactory $uuids,
        private DecimalFactory $decimals,
    ) {}

    public function append(SaleLineCostSnapshot $snapshot): void
    {
        $this->connection->insert('sales.sale_line_cost_snapshot', [
            'organization_id' => $snapshot->organizationId()->toString(),
            'sale_line_id' => $snapshot->saleLineId()->toString(),
            'stock_id' => $snapshot->stockId()->toString(),
            'stock_movement_id' => $snapshot->stockMovementId()->toString(),
            'quantity' => $snapshot->quantity()->toString(),
            'unit_cost' => $snapshot->unitCost()->amount()->toString(),
            'total_cost' => $snapshot->totalCost()->amount()->toString(),
            'currency' => $snapshot->unitCost()->currency()->code(),
            'valuation_version' => $snapshot->valuationVersion(),
            'occurred_at' => $snapshot->occurredAt()->format(DATE_ATOM),
        ]);
    }

    public function findBySaleLine(OrganizationId $organizationId, SaleLineId $saleLineId): ?SaleLineCostSnapshot
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM sales.sale_line_cost_snapshot WHERE organization_id = ? AND sale_line_id = ?',
            [$organizationId->toString(), $saleLineId->toString()],
        );

        return false === $row ? null : $this->snapshot($row);
    }

    public function findBySale(OrganizationId $organizationId, SaleId $saleId): array
    {
        return array_map(
            fn(array $row): SaleLineCostSnapshot => $this->snapshot($row),
            $this->connection->fetchAllAssociative(
                'SELECT snapshot.* FROM sales.sale_line_cost_snapshot snapshot INNER JOIN sales.sale_line line ON line.organization_id = snapshot.organization_id AND line.id = snapshot.sale_line_id WHERE snapshot.organization_id = ? AND line.sale_id = ? ORDER BY line.line_number',
                [$organizationId->toString(), $saleId->toString()],
            ),
        );
    }

    /** @param array<string, mixed> $row */
    private function snapshot(array $row): SaleLineCostSnapshot
    {
        $currency = Currency::fromCode((string) $row['currency']);

        return SaleLineCostSnapshot::reconstitute(
            OrganizationId::fromString((string) $row['organization_id'], $this->uuids),
            SaleLineId::fromString((string) $row['sale_line_id'], $this->uuids),
            StockId::fromString((string) $row['stock_id'], $this->uuids),
            StockMovementId::fromString((string) $row['stock_movement_id'], $this->uuids),
            Quantity::fromString((string) $row['quantity'], $this->decimals),
            Money::fromString((string) $row['unit_cost'], $currency, $this->decimals),
            Money::fromString((string) $row['total_cost'], $currency, $this->decimals),
            (int) $row['valuation_version'],
            new DateTimeImmutable((string) $row['occurred_at']),
        );
    }
}
