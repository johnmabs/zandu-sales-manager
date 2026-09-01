<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\ReconcileStockCount;

use Zandu\SharedKernel\Decimal\Decimal;
use Zandu\SharedKernel\Identity\ProductId;

final readonly class StockCountCostAssignment
{
    public string $reason;

    public function __construct(public ProductId $productId, public Decimal $manualUnitCost, string $reason)
    {
        $reason = trim($reason);
        if ($manualUnitCost->isNegative()) {
            throw new \InvalidArgumentException('Stock count manual unit cost cannot be negative.');
        }
        if ('' === $reason || mb_strlen($reason) > 500) {
            throw new \InvalidArgumentException('Stock count cost assignment reason must contain between 1 and 500 characters.');
        }

        $this->reason = $reason;
    }
}
