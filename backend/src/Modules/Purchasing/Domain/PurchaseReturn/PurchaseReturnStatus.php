<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Domain\PurchaseReturn;

enum PurchaseReturnStatus: string
{
    case Draft = 'DRAFT';
    case Shipped = 'SHIPPED';
    case Cancelled = 'CANCELLED';
}
