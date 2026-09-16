<?php

declare(strict_types=1);

namespace Zandu\Modules\InventoryCosting\Presentation\Api;

use Zandu\Modules\InventoryCosting\Application\StockValuationView;

final readonly class InventoryValuationResourceFactory
{
    public function fromView(StockValuationView $valuation): InventoryValuationResource
    {
        return new InventoryValuationResource(
            $valuation->id,
            $valuation->organizationId,
            $valuation->storeId,
            $valuation->productId,
            $valuation->stockId,
            $valuation->quantityOnHand,
            $valuation->totalValue,
            $valuation->currency,
            $valuation->averageUnitCost,
            $valuation->version,
        );
    }
}
