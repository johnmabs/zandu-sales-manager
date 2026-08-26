<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Domain\StockMovement;

use Zandu\SharedKernel\Identity\{OrganizationId,StockId,StoreId};

interface StockMovementRepository
{
    public function append(StockMovement $movement): void;
    /** @return list<StockMovement> */
    public function findByStock(OrganizationId $organizationId, StockId $stockId): array;
    /** @return list<StockMovement> */
    public function findByStore(OrganizationId $organizationId, StoreId $storeId): array;
    /** @return list<StockMovement> */
    public function findByProduct(OrganizationId $organizationId, StoreId $storeId, \Zandu\SharedKernel\Identity\ProductId $productId): array;
}
