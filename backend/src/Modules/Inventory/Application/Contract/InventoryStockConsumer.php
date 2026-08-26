<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\Contract;

interface InventoryStockConsumer
{
    public function consumeStockForSale(ConsumeStockForSale $request): StockConsumptionResult;
}
