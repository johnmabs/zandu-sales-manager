<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Domain\StockMovement;

enum StockMovementType: string
{
    case InitialStock = 'INITIAL_STOCK';
    case AdjustmentIn = 'ADJUSTMENT_IN';
    case AdjustmentOut = 'ADJUSTMENT_OUT';
    case Sale = 'SALE';

    public function isIncrease(): bool
    {
        return !in_array($this, [self::AdjustmentOut, self::Sale], true);
    }
}
