<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\Contract;

interface InventoryPurchaseReturnShipper
{
    public function ship(ShipPurchaseReturnStock $request): int;
}
