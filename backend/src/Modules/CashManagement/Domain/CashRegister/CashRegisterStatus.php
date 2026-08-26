<?php

declare(strict_types=1);

namespace Zandu\Modules\CashManagement\Domain\CashRegister;

enum CashRegisterStatus: string
{
    case Active = 'ACTIVE';
    case Inactive = 'INACTIVE';
    case Archived = 'ARCHIVED';
}
