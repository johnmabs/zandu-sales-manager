<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\CreateStockCount;

use Zandu\Modules\Inventory\Domain\StockCount\{StockCountMode, StockCountScopeType};
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\{ProductId, StoreId};

final readonly class CreateStockCount
{
    /** @param list<ProductId> $productIds */
    public function __construct(
        public StoreId $storeId,
        public StockCountScopeType $scopeType,
        public array $productIds,
        public ActorContext $actorContext,
        public StockCountMode $mode = StockCountMode::Blind,
    ) {}
}
