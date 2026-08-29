<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\Contract;

interface InventoryGoodsReceiver
{
    public function receive(ReceiveSupplierGoods $request): GoodsReceiptStockResult;
}
