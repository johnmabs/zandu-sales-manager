<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\Contract;

use Zandu\SharedKernel\Identity\{OrganizationId, ProductId, StoreId};

interface CostingStockPositionProvider
{
    public function getForUpdate(
        OrganizationId $organizationId,
        StoreId $storeId,
        ProductId $productId,
    ): CostingStockPosition;
}
