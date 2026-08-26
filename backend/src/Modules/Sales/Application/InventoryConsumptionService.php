<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application;

use LogicException;
use Zandu\Modules\Inventory\Application\Contract\{ConsumeStockForSale,InventoryStockConsumer,StockConsumptionResult};
use Zandu\Modules\Sales\Application\Contract\SaleProductDescriptor;
use Zandu\Modules\Sales\Domain\Sale;

final readonly class InventoryConsumptionService
{
    public function __construct(private InventoryStockConsumer $consumer) {}

    /** @param list<SaleProductDescriptor> $products */
    public function consume(Sale $sale, array $products): ?StockConsumptionResult
    {
        $items = [];
        foreach ($products as $product) {
            if (!$product->inventoryTracked || 'PHYSICAL' !== $product->productType) {
                continue;
            }
            foreach ($sale->lines() as $line) {
                if ($line->productId()->equals($product->productId) && $line->productPackagingId()->equals($product->productPackagingId)) {
                    $items[] = ['productId' => $line->productId(), 'baseQuantity' => $line->baseQuantity()];
                }
            }
        }
        if ([] === $items) {
            return null;
        }
        return $this->consumer->consumeStockForSale(new ConsumeStockForSale($sale->organizationId(), $sale->storeId(), $sale->id(), $items));
    }
}
