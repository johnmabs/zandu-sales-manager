<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application;

enum OverReceiptPolicy: string
{
    case Forbidden = 'FORBIDDEN';
}
