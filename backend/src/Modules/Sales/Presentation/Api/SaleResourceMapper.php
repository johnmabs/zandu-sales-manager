<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Presentation\Api;

use Zandu\Modules\Sales\Application\SaleView;

final readonly class SaleResourceMapper
{
    public function map(SaleView $sale): SaleResource
    {
        return new SaleResource(...array_values($sale->data));
    }
}
