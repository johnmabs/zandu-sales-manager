<?php

declare(strict_types=1);

namespace Zandu\Modules\InventoryCosting\Application;

final readonly class StockValuationView
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $storeId,
        public string $productId,
        public string $stockId,
        public string $quantityOnHand,
        public string $totalValue,
        public string $currency,
        public string $averageUnitCost,
        public int $version,
    ) {}
}
