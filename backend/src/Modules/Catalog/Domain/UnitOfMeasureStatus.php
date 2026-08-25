<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Domain;

enum UnitOfMeasureStatus: string
{
    case Active = 'ACTIVE';
    case Inactive = 'INACTIVE';
}
