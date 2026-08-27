<?php

declare(strict_types=1);

namespace Zandu\Modules\InventoryCosting\Presentation\Api;

final readonly class InitializeStockValuationInput
{
    public function __construct(
        public string $openingUnitCost,
        public string $reason,
    ) {}
}
