<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Infrastructure\Persistence;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use LogicException;
use Zandu\Modules\Sales\Domain\{ReturnSale, ReturnSaleLine, ReturnSaleRepository, ReturnSaleStatus, Sale, SaleLine, SaleLineCostSnapshot, SaleLineCostSnapshotRepository, SaleRepository};
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Identity\{ActorId, OrganizationId, ReturnSaleId, ReturnSaleLineId, SaleId, StoreId, UuidFactory};
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class DbalReturnSaleRepository implements ReturnSaleRepository
{
    public function __construct(
        private Connection $connection,
        private SaleRepository $sales,
        private SaleLineCostSnapshotRepository $costSnapshots,
        private UuidFactory $uuids,
        private DecimalFactory $decimals,
    ) {}

    public function save(ReturnSale $returnSale): void
    {
        $exists = false !== $this->connection->fetchOne(
            'SELECT 1 FROM sales.return_sale WHERE organization_id = ? AND id = ?',
            [$returnSale->organizationId()->toString(), $returnSale->id()->toString()],
        );
        $data = $this->returnData($returnSale);

        if (!$exists) {
            $this->connection->insert('sales.return_sale', $data);
        } else {
            $affected = $this->connection->executeStatement(
                'UPDATE sales.return_sale SET status = :status, business_date = :business_date, completed_by = :completed_by, completed_at = :completed_at, cancelled_by = :cancelled_by, cancelled_at = :cancelled_at, version = :version WHERE organization_id = :organization_id AND id = :id AND version = :expected_version',
                [
                    'status' => $data['status'],
                    'business_date' => $data['business_date'],
                    'completed_by' => $data['completed_by'],
                    'completed_at' => $data['completed_at'],
                    'cancelled_by' => $data['cancelled_by'],
                    'cancelled_at' => $data['cancelled_at'],
                    'version' => $data['version'],
                    'organization_id' => $data['organization_id'],
                    'id' => $data['id'],
                    'expected_version' => $returnSale->version() - 1,
                ],
            );
            if (1 !== $affected) {
                throw new LogicException('Return sale was modified concurrently.');
            }
        }

        foreach ($returnSale->lines() as $index => $line) {
            $existingReturnId = $this->connection->fetchOne(
                'SELECT return_sale_id FROM sales.return_sale_line WHERE organization_id = ? AND id = ?',
                [$returnSale->organizationId()->toString(), $line->id()->toString()],
            );
            if (false !== $existingReturnId) {
                if ((string) $existingReturnId !== $returnSale->id()->toString()) {
                    throw new LogicException('Return sale line identity already belongs to another return.');
                }

                continue;
            }
            $this->connection->executeStatement(
                'INSERT INTO sales.return_sale_line (id, organization_id, return_sale_id, sale_id, line_number, sale_line_id, product_id, returned_quantity, base_returned_quantity, restock, reason) VALUES (:id, :organization_id, :return_sale_id, :sale_id, :line_number, :sale_line_id, :product_id, :returned_quantity, :base_returned_quantity, :restock, :reason)',
                [
                    'id' => $line->id()->toString(),
                    'organization_id' => $returnSale->organizationId()->toString(),
                    'return_sale_id' => $returnSale->id()->toString(),
                    'sale_id' => $returnSale->saleId()->toString(),
                    'line_number' => $index + 1,
                    'sale_line_id' => $line->saleLineId()->toString(),
                    'product_id' => $line->productId()->toString(),
                    'returned_quantity' => $line->returnedQuantity()->toString(),
                    'base_returned_quantity' => $line->baseReturnedQuantity()->toString(),
                    'restock' => $line->restock() ? 1 : 0,
                    'reason' => $line->reason(),
                ],
            );
        }
    }

    public function get(OrganizationId $organizationId, ReturnSaleId $returnSaleId): ReturnSale
    {
        return $this->load($organizationId, $returnSaleId, false);
    }

    public function getForUpdate(OrganizationId $organizationId, ReturnSaleId $returnSaleId): ReturnSale
    {
        return $this->load($organizationId, $returnSaleId, true);
    }

    public function findBySale(OrganizationId $organizationId, SaleId $saleId): array
    {
        $ids = $this->connection->fetchFirstColumn(
            'SELECT id FROM sales.return_sale WHERE organization_id = ? AND sale_id = ? ORDER BY created_at, id',
            [$organizationId->toString(), $saleId->toString()],
        );

        return array_map(
            fn(mixed $id): ReturnSale => $this->get($organizationId, ReturnSaleId::fromString((string) $id, $this->uuids)),
            $ids,
        );
    }

    private function load(OrganizationId $organizationId, ReturnSaleId $returnSaleId, bool $forUpdate): ReturnSale
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM sales.return_sale WHERE organization_id = ? AND id = ?' . ($forUpdate ? ' FOR UPDATE' : ''),
            [$organizationId->toString(), $returnSaleId->toString()],
        );
        if (false === $row) {
            throw new LogicException('Return sale not found.');
        }

        $saleId = SaleId::fromString((string) $row['sale_id'], $this->uuids);
        $sourceSale = $this->sales->get($organizationId, $saleId);
        $sourceLines = [];
        foreach ($sourceSale->lines() as $line) {
            $sourceLines[$line->id()->toString()] = $line;
        }
        $costs = [];
        foreach ($this->costSnapshots->findBySale($organizationId, $saleId) as $snapshot) {
            $costs[$snapshot->saleLineId()->toString()] = $snapshot;
        }
        $lineRows = $this->connection->fetchAllAssociative(
            'SELECT * FROM sales.return_sale_line WHERE organization_id = ? AND return_sale_id = ? ORDER BY line_number',
            [$organizationId->toString(), $returnSaleId->toString()],
        );

        return ReturnSale::reconstitute(
            $returnSaleId,
            $organizationId,
            StoreId::fromString((string) $row['store_id'], $this->uuids),
            $saleId,
            ReturnSaleStatus::from((string) $row['status']),
            null === $row['reason'] ? null : (string) $row['reason'],
            ActorId::fromString((string) $row['created_by'], $this->uuids),
            new DateTimeImmutable((string) $row['created_at']),
            null === $row['business_date'] ? null : (string) $row['business_date'],
            $this->actor($row['completed_by'] ?? null),
            $this->date($row['completed_at'] ?? null),
            $this->actor($row['cancelled_by'] ?? null),
            $this->date($row['cancelled_at'] ?? null),
            (int) $row['version'],
            array_map(fn(array $line): ReturnSaleLine => $this->line($line, $sourceSale, $sourceLines, $costs), $lineRows),
        );
    }

    /** @return array<string, mixed> */
    private function returnData(ReturnSale $returnSale): array
    {
        return [
            'id' => $returnSale->id()->toString(),
            'organization_id' => $returnSale->organizationId()->toString(),
            'store_id' => $returnSale->storeId()->toString(),
            'sale_id' => $returnSale->saleId()->toString(),
            'status' => $returnSale->status()->value,
            'reason' => $returnSale->reason(),
            'business_date' => $returnSale->businessDate(),
            'created_by' => $returnSale->createdBy()->toString(),
            'created_at' => $returnSale->createdAt()->format(DATE_ATOM),
            'completed_by' => $returnSale->completedBy()?->toString(),
            'completed_at' => $returnSale->completedAt()?->format(DATE_ATOM),
            'cancelled_by' => $returnSale->cancelledBy()?->toString(),
            'cancelled_at' => $returnSale->cancelledAt()?->format(DATE_ATOM),
            'version' => $returnSale->version(),
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, SaleLine> $sourceLines
     * @param array<string, SaleLineCostSnapshot> $costs
     */
    private function line(array $row, Sale $sourceSale, array $sourceLines, array $costs): ReturnSaleLine
    {
        $saleLineId = (string) $row['sale_line_id'];
        $original = $sourceLines[$saleLineId] ?? throw new LogicException('Original sale line not found.');
        $line = new ReturnSaleLine(
            ReturnSaleLineId::fromString((string) $row['id'], $this->uuids),
            $original,
            $costs[$saleLineId] ?? null,
            Quantity::fromString((string) $row['returned_quantity'], $this->decimals),
            (bool) $row['restock'],
            null === $row['reason'] ? null : (string) $row['reason'],
        );
        if (!$sourceSale->id()->equals($original->saleId()) || !$line->baseReturnedQuantity()->equals(Quantity::fromString((string) $row['base_returned_quantity'], $this->decimals))) {
            throw new LogicException('Persisted return line snapshots are inconsistent.');
        }

        return $line;
    }

    private function actor(mixed $value): ?ActorId
    {
        return null === $value ? null : ActorId::fromString((string) $value, $this->uuids);
    }

    private function date(mixed $value): ?DateTimeImmutable
    {
        return null === $value ? null : new DateTimeImmutable((string) $value);
    }
}
