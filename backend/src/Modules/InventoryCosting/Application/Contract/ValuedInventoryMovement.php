<?php

declare(strict_types=1);

namespace Zandu\Modules\InventoryCosting\Application\Contract;

use DateTimeImmutable;
use Zandu\SharedKernel\Identity\{StockId, StockMovementId};
use Zandu\SharedKernel\Money\Money;
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class ValuedInventoryMovement
{
    public function __construct(
        public StockId $stockId,
        public StockMovementId $stockMovementId,
        public Quantity $quantity,
        public Money $unitCost,
        public Money $totalCost,
        public int $valuationVersion,
        public DateTimeImmutable $occurredAt,
    ) {}
}
