<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\Contract;

interface InventoryGoodsReceiptCorrector
{
    public function apply(ApplyGoodsReceiptCorrection $correction): int;
}
