<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Domain\StockCount;

enum StockCountScopeType: string
{
    case Full = 'FULL';
    case Partial = 'PARTIAL';
}
