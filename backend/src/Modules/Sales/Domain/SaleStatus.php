<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Domain;

enum SaleStatus: string
{
    case Draft = 'DRAFT';
    case AwaitingPayment = 'AWAITING_PAYMENT';
    case Completed = 'COMPLETED';
    case Cancelled = 'CANCELLED';
}
