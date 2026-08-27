<?php

declare(strict_types=1);

namespace Zandu\Modules\InventoryCosting\Application;

use Zandu\Modules\InventoryCosting\Domain\Valuation\StockValuation;

final readonly class StockValuationViewFactory
{
    public function from(StockValuation $valuation): StockValuationView
    {
        return new StockValuationView(
            $valuation->id()->toString(),
            $valuation->organizationId()->toString(),
            $valuation->storeId()->toString(),
            $valuation->productId()->toString(),
            $valuation->stockId()->toString(),
            $valuation->quantityOnHand()->toString(),
            $valuation->totalValue()->amount()->toString(),
            $valuation->currency()->code(),
            $valuation->averageUnitCost()->amount()->toString(),
            $valuation->version(),
        );
    }
}
