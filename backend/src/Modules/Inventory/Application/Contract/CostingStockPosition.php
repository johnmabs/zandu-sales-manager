<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\Contract;

use Zandu\SharedKernel\Identity\{ProductId, StockId, StoreId};
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class CostingStockPosition
{
    public function __construct(
        public StockId $stockId,
        public StoreId $storeId,
        public ProductId $productId,
        public Quantity $quantityOnHand,
        public int $version,
    ) {}
}
