<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\Contract;

interface InventoryStockTransferShipper
{
    public function ship(ShipStockTransferStock $request): StockTransferStockResult;
}
