<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\Contract;

interface InventoryStockRestocker
{
    public function restockSaleReturn(RestockSaleReturn $request): StockRestockResult;
}
