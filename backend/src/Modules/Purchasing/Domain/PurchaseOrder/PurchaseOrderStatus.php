<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Domain\PurchaseOrder;

enum PurchaseOrderStatus: string
{
    case Draft = 'DRAFT';
    case Confirmed = 'CONFIRMED';
    case PartiallyReceived = 'PARTIALLY_RECEIVED';
    case FullyReceived = 'FULLY_RECEIVED';
    case Closed = 'CLOSED';
    case Cancelled = 'CANCELLED';
}
