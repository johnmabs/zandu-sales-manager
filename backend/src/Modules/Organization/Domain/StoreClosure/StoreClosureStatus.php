<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Domain\StoreClosure;

enum StoreClosureStatus: string
{
    case Requested = 'REQUESTED';
    case InProgress = 'IN_PROGRESS';
    case Ready = 'READY';
    case Completed = 'COMPLETED';
    case Cancelled = 'CANCELLED';
}
