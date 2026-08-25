<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Domain\Category;

enum CategoryStatus: string
{
    case Active = 'ACTIVE';
    case Inactive = 'INACTIVE';
    case Archived = 'ARCHIVED';
}
