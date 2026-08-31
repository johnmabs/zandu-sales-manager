<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Domain\StockCount;

use Zandu\SharedKernel\Identity\{OrganizationId, StockCountId};

interface StockCountRepository
{
    public function save(StockCount $stockCount): void;
    public function get(OrganizationId $organizationId, StockCountId $stockCountId): StockCount;
    public function getForUpdate(OrganizationId $organizationId, StockCountId $stockCountId): StockCount;
    public function find(OrganizationId $organizationId, StockCountId $stockCountId): ?StockCount;
}
