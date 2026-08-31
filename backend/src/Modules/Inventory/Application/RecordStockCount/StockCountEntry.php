<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\RecordStockCount;

use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class StockCountEntry
{
    public function __construct(public ProductId $productId, public Quantity $countedQuantity, public int $expectedLineVersion)
    {
        if ($expectedLineVersion < 1) {
            throw new \InvalidArgumentException('Expected stock count line version must be positive.');
        }
    }
}
