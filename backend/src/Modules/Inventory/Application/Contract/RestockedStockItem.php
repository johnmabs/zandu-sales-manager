<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\Contract;

use DateTimeImmutable;
use Zandu\SharedKernel\Identity\{ProductId, StockId, StockMovementId};
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class RestockedStockItem
{
    public function __construct(
        public ProductId $productId,
        public StockId $stockId,
        public StockMovementId $stockMovementId,
        public Quantity $quantity,
        public Quantity $previousQuantity,
        public Quantity $resultingQuantity,
        public DateTimeImmutable $occurredAt,
    ) {}
}
