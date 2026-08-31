<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\Contract;

interface InventoryStockTransferReceiver
{
    public function receive(ReceiveStockTransferStock $request): StockTransferStockResult;
}
