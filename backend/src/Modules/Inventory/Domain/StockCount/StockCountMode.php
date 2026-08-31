<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Domain\StockCount;

enum StockCountMode: string
{
    case Blind = 'BLIND';
    case Guided = 'GUIDED';
}
