<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Domain\Supplier;

enum SupplierStatus: string
{
    case Active = 'ACTIVE';
    case Inactive = 'INACTIVE';
    case Archived = 'ARCHIVED';
}
