<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Presentation\Api;

final readonly class StockTransferLineInput
{
    public function __construct(public string $productId, public string $requestedQuantity) {}
}
