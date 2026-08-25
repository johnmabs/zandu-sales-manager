<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Domain\PriceList;

enum PriceListScope: string
{
    case Organization = 'ORGANIZATION';
}
