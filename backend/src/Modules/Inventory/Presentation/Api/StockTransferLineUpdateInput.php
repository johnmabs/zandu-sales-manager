<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Presentation\Api;

final readonly class StockTransferLineUpdateInput
{
    public function __construct(public string $requestedQuantity) {}
}
