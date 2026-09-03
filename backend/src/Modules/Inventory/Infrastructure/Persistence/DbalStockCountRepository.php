<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Infrastructure\Persistence;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use LogicException;
use Zandu\Modules\Inventory\Domain\StockCount\{StockCount, StockCountMode, StockCountRepository, StockCountScopeType, StockCountStatus};
use Zandu\Modules\Inventory\Domain\StockCount\StockCountNotFound;
use Zandu\SharedKernel\Identity\{ActorId, OrganizationId, ProductId, StockCountId, StoreId, UuidFactory};

final readonly class DbalStockCountRepository implements StockCountRepository
{
    public function __construct(private Connection $db, private UuidFactory $ids) {}

    public function save(StockCount $stockCount): void
    {
        $data = $this->data($stockCount);
        if (false === $this->db->fetchOne('SELECT 1 FROM inventory.stock_count WHERE organization_id=? AND id=?', [$data['organization_id'], $data['id']])) {
            $this->db->insert('inventory.stock_count', $data);

            return;
        }
        $affected = $this->db->executeStatement('UPDATE inventory.stock_count SET status=:status,total_line_count=:total_line_count,counted_line_count=:counted_line_count,reconciled_line_count=:reconciled_line_count,started_by=:started_by,started_at=:started_at,finalization_started_by=:finalization_started_by,finalization_started_at=:finalization_started_at,completed_by=:completed_by,completed_at=:completed_at,cancelled_by=:cancelled_by,cancelled_at=:cancelled_at,version=:version WHERE organization_id=:organization_id AND id=:id AND version=:expected_version', $data + ['expected_version' => $stockCount->version() - 1]);
        if (1 !== $affected) {
            throw new LogicException('Stock count was modified concurrently.');
        }
    }

    public function get(OrganizationId $organizationId, StockCountId $stockCountId): StockCount
    {
        return $this->find($organizationId, $stockCountId) ?? throw StockCountNotFound::withId($stockCountId);
    }

    public function getForUpdate(OrganizationId $organizationId, StockCountId $stockCountId): StockCount
    {
        if (false === $this->db->fetchOne('SELECT id FROM inventory.stock_count WHERE organization_id=? AND id=? FOR UPDATE', [$organizationId->toString(), $stockCountId->toString()])) {
            throw StockCountNotFound::withId($stockCountId);
        }

        return $this->get($organizationId, $stockCountId);
    }

    public function find(OrganizationId $organizationId, StockCountId $stockCountId): ?StockCount
    {
        $row = $this->db->fetchAssociative('SELECT * FROM inventory.stock_count WHERE organization_id=? AND id=?', [$organizationId->toString(), $stockCountId->toString()]);
        if (false === $row) {
            return null;
        }

        return StockCount::reconstitute(
            $stockCountId,
            $organizationId,
            StoreId::fromString((string) $row['store_id'], $this->ids),
            StockCountStatus::from((string) $row['status']),
            StockCountMode::from((string) $row['mode']),
            StockCountScopeType::from((string) $row['scope_type']),
            $this->productIds((string) $row['requested_product_ids']),
            (int) $row['total_line_count'],
            (int) $row['counted_line_count'],
            (int) $row['reconciled_line_count'],
            ActorId::fromString((string) $row['created_by'], $this->ids),
            new DateTimeImmutable((string) $row['created_at']),
            $this->actor($row['started_by']),
            $this->date($row['started_at']),
            $this->actor($row['finalization_started_by']),
            $this->date($row['finalization_started_at']),
            $this->actor($row['completed_by']),
            $this->date($row['completed_at']),
            $this->actor($row['cancelled_by']),
            $this->date($row['cancelled_at']),
            (int) $row['version'],
        );
    }

    public function hasOpenForStore(OrganizationId $organizationId, StoreId $storeId): bool
    {
        return false !== $this->db->fetchOne(
            "SELECT 1 FROM inventory.stock_count WHERE organization_id=? AND store_id=? AND status IN ('OPEN','FINALIZING') LIMIT 1",
            [$organizationId->toString(), $storeId->toString()],
        );
    }

    public function findAll(OrganizationId $organizationId): array
    {
        return array_map(
            fn(string $id): StockCount => $this->get($organizationId, StockCountId::fromString($id, $this->ids)),
            $this->db->fetchFirstColumn('SELECT id FROM inventory.stock_count WHERE organization_id=? ORDER BY created_at DESC,id DESC', [$organizationId->toString()]),
        );
    }

    /** @return array<string, mixed> */
    private function data(StockCount $stockCount): array
    {
        return [
            'id' => $stockCount->id()->toString(),
            'organization_id' => $stockCount->organizationId()->toString(),
            'store_id' => $stockCount->storeId()->toString(),
            'status' => $stockCount->status()->value,
            'mode' => $stockCount->mode()->value,
            'scope_type' => $stockCount->scopeType()->value,
            'requested_product_ids' => '{' . implode(',', array_map(static fn(ProductId $id): string => $id->toString(), $stockCount->requestedProductIds())) . '}',
            'total_line_count' => $stockCount->totalLineCount(),
            'counted_line_count' => $stockCount->countedLineCount(),
            'reconciled_line_count' => $stockCount->reconciledLineCount(),
            'created_by' => $stockCount->createdBy()->toString(),
            'created_at' => $stockCount->createdAt()->format(DATE_ATOM),
            'started_by' => $stockCount->startedBy()?->toString(),
            'started_at' => $stockCount->startedAt()?->format(DATE_ATOM),
            'finalization_started_by' => $stockCount->finalizationStartedBy()?->toString(),
            'finalization_started_at' => $stockCount->finalizationStartedAt()?->format(DATE_ATOM),
            'completed_by' => $stockCount->completedBy()?->toString(),
            'completed_at' => $stockCount->completedAt()?->format(DATE_ATOM),
            'cancelled_by' => $stockCount->cancelledBy()?->toString(),
            'cancelled_at' => $stockCount->cancelledAt()?->format(DATE_ATOM),
            'version' => $stockCount->version(),
        ];
    }

    /** @return list<ProductId> */
    private function productIds(string $postgresArray): array
    {
        $values = trim($postgresArray, '{}');
        if ('' === $values) {
            return [];
        }

        return array_map(fn(string $id): ProductId => ProductId::fromString($id, $this->ids), explode(',', $values));
    }

    private function actor(mixed $value): ?ActorId
    {
        return null === $value ? null : ActorId::fromString((string) $value, $this->ids);
    }

    private function date(mixed $value): ?DateTimeImmutable
    {
        return null === $value ? null : new DateTimeImmutable((string) $value);
    }
}
