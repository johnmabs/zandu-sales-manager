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
}
