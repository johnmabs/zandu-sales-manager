<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Domain\StockCount;

enum StockCountReconciliationStatus: string
{
    case Pending = 'PENDING';
    case Reconciled = 'RECONCILED';
}
