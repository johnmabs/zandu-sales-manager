<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Domain\StockCount;

use Zandu\SharedKernel\Identity\{OrganizationId, ProductId, StockCountId, StoreId};

interface OpenStockCountScopeRepository
{
    /** @param list<ProductId> $productIds */
    public function acquire(OrganizationId $organizationId, StoreId $storeId, StockCountId $stockCountId, array $productIds): void;

    public function isLocked(OrganizationId $organizationId, StoreId $storeId, ProductId $productId): bool;

    public function release(OrganizationId $organizationId, StockCountId $stockCountId): void;
}
