<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Domain\StockTransfer;

enum StockTransferStatus: string
{
    case Draft = 'DRAFT';
    case Shipped = 'SHIPPED';
    case Received = 'RECEIVED';
    case Cancelled = 'CANCELLED';
}
