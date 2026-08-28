<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Domain;

enum ReturnSaleStatus: string
{
    case Draft = 'DRAFT';
    case Completed = 'COMPLETED';
    case Cancelled = 'CANCELLED';
}
