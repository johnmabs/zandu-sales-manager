<?php

declare(strict_types=1);

namespace Zandu\Modules\CashManagement\Domain\CashSession;

enum CashSessionStatus: string
{
    case Open = 'OPEN';
    case Closed = 'CLOSED';
}
