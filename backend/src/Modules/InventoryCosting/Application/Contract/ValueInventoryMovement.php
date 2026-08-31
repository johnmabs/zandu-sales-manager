<?php

declare(strict_types=1);

namespace Zandu\Modules\InventoryCosting\Application\Contract;

use DateTimeImmutable;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Decimal\Decimal;
use Zandu\SharedKernel\Identity\{ProductId, StockId, StockMovementId, StoreId};
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class ValueInventoryMovement
{
    public function __construct(
        public StoreId $storeId,
        public ProductId $productId,
        public StockId $stockId,
        public StockMovementId $stockMovementId,
        public InventoryCostingMovementType $type,
        public Quantity $quantity,
        public Quantity $previousQuantity,
        public Quantity $resultingQuantity,
        public ?Decimal $incomingUnitCost,
        public string $reason,
        public DateTimeImmutable $occurredAt,
        public ActorContext $actorContext,
        public bool $initializeIfMissing = false,
    ) {}
}
