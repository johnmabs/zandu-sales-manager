<?php

declare(strict_types=1);

namespace Zandu\Modules\InventoryCosting\Application\Contract;

enum InventoryCostingMovementType: string
{
    case InitialStock = 'INITIAL_STOCK';
    case AdjustmentIn = 'ADJUSTMENT_IN';
    case AdjustmentOut = 'ADJUSTMENT_OUT';
    case Sale = 'SALE';
    case SaleReturn = 'SALE_RETURN';

    public function isIncoming(): bool
    {
        return !in_array($this, [self::AdjustmentOut, self::Sale], true);
    }
}
