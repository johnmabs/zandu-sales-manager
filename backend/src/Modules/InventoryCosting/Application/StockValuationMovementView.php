<?php

declare(strict_types=1);

namespace Zandu\Modules\InventoryCosting\Application;

final readonly class StockValuationMovementView
{
    public function __construct(
        public string $id,
        public string $stockValuationId,
        public string $organizationId,
        public string $storeId,
        public string $productId,
        public string $stockId,
        public ?string $stockMovementId,
        public string $type,
        public string $quantity,
        public string $unitCost,
        public string $value,
        public string $previousTotalValue,
        public string $resultingTotalValue,
        public string $previousAverageCost,
        public string $resultingAverageCost,
        public string $currency,
        public string $sourceType,
        public ?string $sourceReferenceId,
        public string $occurredAt,
        public string $correlationId,
    ) {}
}
