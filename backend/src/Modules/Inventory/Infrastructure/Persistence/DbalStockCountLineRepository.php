<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Infrastructure\Persistence;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use LogicException;
use Zandu\Modules\Inventory\Domain\StockCount\{StockCountLine, StockCountLineRepository, StockCountReconciliationStatus};
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Identity\{ActorId, OrganizationId, ProductId, StockCountId, StockCountLineId, StoreId, UuidFactory};
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class DbalStockCountLineRepository implements StockCountLineRepository
{
    public function __construct(private Connection $db, private UuidFactory $ids, private DecimalFactory $decimals) {}

    public function save(StockCountLine $line): void
    {
        $data = $this->data($line);
        if (false === $this->db->fetchOne('SELECT 1 FROM inventory.stock_count_line WHERE organization_id=? AND id=?', [$data['organization_id'], $data['id']])) {
            $this->db->insert('inventory.stock_count_line', $data);

            return;
        }
        $affected = $this->db->executeStatement('UPDATE inventory.stock_count_line SET counted_quantity=:counted_quantity,counted_by=:counted_by,counted_at=:counted_at,revision=:revision,reconciliation_status=:reconciliation_status,version=:version WHERE organization_id=:organization_id AND id=:id AND version=:expected_version', $data + ['expected_version' => $line->version() - 1]);
        if (1 !== $affected) {
            throw new LogicException('Stock count line was modified concurrently.');
        }
    }

    public function findByStockCount(OrganizationId $organizationId, StockCountId $stockCountId): array
    {
        return array_map(fn(array $row): StockCountLine => $this->aggregate($row), $this->db->fetchAllAssociative('SELECT * FROM inventory.stock_count_line WHERE organization_id=? AND stock_count_id=? ORDER BY product_id', [$organizationId->toString(), $stockCountId->toString()]));
    }

    public function findByProduct(OrganizationId $organizationId, StockCountId $stockCountId, ProductId $productId): ?StockCountLine
    {
        $row = $this->db->fetchAssociative('SELECT * FROM inventory.stock_count_line WHERE organization_id=? AND stock_count_id=? AND product_id=?', [$organizationId->toString(), $stockCountId->toString(), $productId->toString()]);

        return false === $row ? null : $this->aggregate($row);
    }

    public function getForUpdateByProduct(OrganizationId $organizationId, StockCountId $stockCountId, ProductId $productId): StockCountLine
    {
        $row = $this->db->fetchAssociative('SELECT * FROM inventory.stock_count_line WHERE organization_id=? AND stock_count_id=? AND product_id=? FOR UPDATE', [$organizationId->toString(), $stockCountId->toString(), $productId->toString()]);
        if (false === $row) {
            throw new LogicException('Stock count line was not found.');
        }

        return $this->aggregate($row);
    }

    public function countUncountedForUpdate(OrganizationId $organizationId, StockCountId $stockCountId): int
    {
        $rows = $this->db->fetchAllAssociative('SELECT counted_quantity FROM inventory.stock_count_line WHERE organization_id=? AND stock_count_id=? ORDER BY product_id FOR UPDATE', [$organizationId->toString(), $stockCountId->toString()]);

        return count(array_filter($rows, static fn(array $row): bool => null === $row['counted_quantity']));
    }

    /** @param array<string, mixed> $row */
    private function aggregate(array $row): StockCountLine
    {
        return StockCountLine::reconstitute(
            StockCountLineId::fromString((string) $row['id'], $this->ids),
            StockCountId::fromString((string) $row['stock_count_id'], $this->ids),
            OrganizationId::fromString((string) $row['organization_id'], $this->ids),
            StoreId::fromString((string) $row['store_id'], $this->ids),
            ProductId::fromString((string) $row['product_id'], $this->ids),
            Quantity::fromString((string) $row['expected_quantity'], $this->decimals),
            null === $row['counted_quantity'] ? null : Quantity::fromString((string) $row['counted_quantity'], $this->decimals),
            null === $row['counted_by'] ? null : ActorId::fromString((string) $row['counted_by'], $this->ids),
            null === $row['counted_at'] ? null : new DateTimeImmutable((string) $row['counted_at']),
            (int) $row['revision'],
            StockCountReconciliationStatus::from((string) $row['reconciliation_status']),
            (int) $row['version'],
        );
    }

    /** @return array<string, mixed> */
    private function data(StockCountLine $line): array
    {
        return [
            'id' => $line->id()->toString(),
            'organization_id' => $line->organizationId()->toString(),
            'stock_count_id' => $line->stockCountId()->toString(),
            'store_id' => $line->storeId()->toString(),
            'product_id' => $line->productId()->toString(),
            'expected_quantity' => $line->expectedQuantity()->toString(),
            'counted_quantity' => $line->countedQuantity()?->toString(),
            'counted_by' => $line->countedBy()?->toString(),
            'counted_at' => $line->countedAt()?->format(DATE_ATOM),
            'revision' => $line->revision(),
            'reconciliation_status' => $line->reconciliationStatus()->value,
            'version' => $line->version(),
        ];
    }
}
