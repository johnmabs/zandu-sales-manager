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

    /** @param list<ProductId> $productIds */
    public static function fromStrings(StoreId $storeId, string $scopeType, array $productIds, ActorContext $actorContext, string $mode = 'BLIND'): self
    {
        try {
            return new self(
                $storeId,
                StockCountScopeType::from($scopeType),
                $productIds,
                $actorContext,
                StockCountMode::from($mode),
            );
        } catch (\ValueError $exception) {
            throw new \InvalidArgumentException('Stock count scope type or mode is invalid.', previous: $exception);
        }
    }
}
