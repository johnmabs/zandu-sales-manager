<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Domain\ProductPrice;

enum ProductPriceStatus: string
{
    case Active = 'ACTIVE';
    case Inactive = 'INACTIVE';
    case Archived = 'ARCHIVED';
}
