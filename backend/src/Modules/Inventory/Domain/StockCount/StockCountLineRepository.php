<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Domain\StockCount;

use Zandu\SharedKernel\Identity\{OrganizationId, ProductId, StockCountId};

interface StockCountLineRepository
{
    public function save(StockCountLine $line): void;

    /** @return list<StockCountLine> */
    public function findByStockCount(OrganizationId $organizationId, StockCountId $stockCountId): array;

    public function findByProduct(OrganizationId $organizationId, StockCountId $stockCountId, ProductId $productId): ?StockCountLine;

    public function getForUpdateByProduct(OrganizationId $organizationId, StockCountId $stockCountId, ProductId $productId): StockCountLine;

    /** Locks every line so no entry can change until the surrounding transaction ends. */
    public function countUncountedForUpdate(OrganizationId $organizationId, StockCountId $stockCountId): int;

    /** @return list<StockCountLine> */
    public function findPendingForUpdate(OrganizationId $organizationId, StockCountId $stockCountId, int $limit): array;

    /** Locks every pending line until the surrounding transaction ends. */
    public function countPendingForUpdate(OrganizationId $organizationId, StockCountId $stockCountId): int;
}
