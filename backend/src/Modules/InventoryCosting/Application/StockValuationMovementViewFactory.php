<?php

declare(strict_types=1);

namespace Zandu\Modules\InventoryCosting\Application;

use Zandu\Modules\InventoryCosting\Domain\ValuationMovement\StockValuationMovement;

final readonly class StockValuationMovementViewFactory
{
    public function from(StockValuationMovement $movement): StockValuationMovementView
    {
        return new StockValuationMovementView(
            $movement->id()->toString(),
            $movement->stockValuationId()->toString(),
            $movement->organizationId()->toString(),
            $movement->storeId()->toString(),
            $movement->productId()->toString(),
            $movement->stockId()->toString(),
            $movement->stockMovementId()?->toString(),
            $movement->type()->value,
            $movement->quantity()->toString(),
            $movement->unitCost()->amount()->toString(),
            $movement->value()->amount()->toString(),
            $movement->previousTotalValue()->amount()->toString(),
            $movement->resultingTotalValue()->amount()->toString(),
            $movement->previousAverageCost()->amount()->toString(),
            $movement->resultingAverageCost()->amount()->toString(),
            $movement->value()->currency()->code(),
            $movement->source()->type(),
            $movement->source()->referenceId(),
            $movement->occurredAt()->format(DATE_ATOM),
            $movement->correlationId()->toString(),
        );
    }
}
