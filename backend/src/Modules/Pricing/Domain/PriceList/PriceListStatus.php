<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Domain\PriceList;

enum PriceListStatus: string
{
    case Draft = 'DRAFT';
    case Active = 'ACTIVE';
    case Inactive = 'INACTIVE';
    case Archived = 'ARCHIVED';
}
