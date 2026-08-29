<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Domain\GoodsReceipt;

enum GoodsReceiptStatus: string
{
    case Draft = 'DRAFT';
    case Posted = 'POSTED';
    case Cancelled = 'CANCELLED';
}
