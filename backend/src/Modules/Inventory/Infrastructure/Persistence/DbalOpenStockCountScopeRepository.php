<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Infrastructure\Persistence;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Zandu\Modules\Inventory\Domain\InventoryRuleViolation;
use Zandu\Modules\Inventory\Domain\StockCount\OpenStockCountScopeRepository;
use Zandu\SharedKernel\Identity\{OrganizationId, ProductId, StockCountId, StoreId};

final readonly class DbalOpenStockCountScopeRepository implements OpenStockCountScopeRepository
{
    public function __construct(private Connection $db) {}

    public function acquire(OrganizationId $organizationId, StoreId $storeId, StockCountId $stockCountId, array $productIds): void
    {
        usort($productIds, static fn(ProductId $left, ProductId $right): int => $left->toString() <=> $right->toString());
        foreach ($productIds as $productId) {
            $this->lockPosition($organizationId, $storeId, $productId);
            try {
                $this->db->insert('inventory.open_stock_count_scope', ['organization_id' => $organizationId->toString(), 'store_id' => $storeId->toString(), 'product_id' => $productId->toString(), 'stock_count_id' => $stockCountId->toString()]);
            } catch (UniqueConstraintViolationException) {
                throw InventoryRuleViolation::with('STOCK_COUNT_ALREADY_OPEN_FOR_PRODUCT', 'Another open stock count already contains a requested product.');
            }
        }
    }

    public function isLocked(OrganizationId $organizationId, StoreId $storeId, ProductId $productId): bool
    {
        $this->lockPosition($organizationId, $storeId, $productId);

        return false !== $this->db->fetchOne('SELECT 1 FROM inventory.open_stock_count_scope WHERE organization_id=? AND store_id=? AND product_id=?', [$organizationId->toString(), $storeId->toString(), $productId->toString()]);
    }

    public function release(OrganizationId $organizationId, StockCountId $stockCountId): void
    {
        $this->db->delete('inventory.open_stock_count_scope', ['organization_id' => $organizationId->toString(), 'stock_count_id' => $stockCountId->toString()]);
    }

    private function lockPosition(OrganizationId $organizationId, StoreId $storeId, ProductId $productId): void
    {
        $this->db->fetchOne('SELECT pg_advisory_xact_lock(hashtextextended(?,0))', [implode(':', [$organizationId->toString(), $storeId->toString(), $productId->toString()])]);
    }
}
