<?php

declare(strict_types=1);

namespace Zandu\Modules\InventoryCosting\Domain\ValuationMovement;

use Zandu\SharedKernel\Identity\{OrganizationId, StockValuationId, StoreId};

interface StockValuationMovementRepository
{
    public function append(StockValuationMovement $movement): void;

    public function appendOnce(StockValuationMovement $movement): bool;

    /** @return list<StockValuationMovement> */
    public function findByValuation(OrganizationId $organizationId, StockValuationId $valuationId): array;

    /** @return list<StockValuationMovement> */
    public function findByStore(OrganizationId $organizationId, StoreId $storeId): array;
}
