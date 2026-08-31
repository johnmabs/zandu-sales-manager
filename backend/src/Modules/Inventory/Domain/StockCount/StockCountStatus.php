<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Domain\StockCount;

enum StockCountStatus: string
{
    case Draft = 'DRAFT';
    case Open = 'OPEN';
    case Finalizing = 'FINALIZING';
    case Completed = 'COMPLETED';
    case Cancelled = 'CANCELLED';
}
