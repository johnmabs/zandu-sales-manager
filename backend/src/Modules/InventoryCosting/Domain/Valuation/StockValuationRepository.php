<?php

declare(strict_types=1);

namespace Zandu\Modules\InventoryCosting\Domain\Valuation;

use Zandu\SharedKernel\Identity\{OrganizationId, StockId, StoreId};

interface StockValuationRepository
{
    public function save(StockValuation $valuation): void;

    public function findByStock(OrganizationId $organizationId, StockId $stockId): ?StockValuation;

    /** @throws \Zandu\Modules\InventoryCosting\Domain\InventoryCostingRuleViolation */
    public function getByStock(OrganizationId $organizationId, StockId $stockId): StockValuation;

    /** @throws \Zandu\Modules\InventoryCosting\Domain\InventoryCostingRuleViolation */
    public function getByStockForUpdate(OrganizationId $organizationId, StockId $stockId): StockValuation;

    /** @return list<StockValuation> */
    public function findByStore(OrganizationId $organizationId, StoreId $storeId): array;
}
