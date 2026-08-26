<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Domain\Stock;

use Zandu\Modules\Inventory\Domain\Stock\MovementQuantity;

use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\StockId;
use Zandu\SharedKernel\Identity\StoreId;

interface StockRepository
{
    public function save(Stock $stock): void;

    /** @throws StockNotFound */
    public function get(OrganizationId $organizationId, StoreId $storeId, ProductId $productId): Stock;

    public function find(OrganizationId $organizationId, StoreId $storeId, ProductId $productId): ?Stock;

    /** @throws StockNotFound */
    public function getById(OrganizationId $organizationId, StockId $stockId): Stock;

    public function decreaseIfAvailable(OrganizationId $organizationId, StockId $stockId, MovementQuantity $quantity, int $expectedVersion): bool;
}
