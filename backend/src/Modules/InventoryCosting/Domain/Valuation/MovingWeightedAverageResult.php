<?php

declare(strict_types=1);

namespace Zandu\Modules\InventoryCosting\Domain\Valuation;

use Zandu\SharedKernel\Money\Money;
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class MovingWeightedAverageResult
{
    public function __construct(
        public Quantity $resultingQuantity,
        public Money $resultingTotalValue,
        public Money $movementUnitCost,
        public Money $movementValue,
        public Money $resultingAverageUnitCost,
    ) {}
}
