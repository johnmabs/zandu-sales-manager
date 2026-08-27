<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application;

use LogicException;
use Zandu\Modules\Catalog\Application\Contract\InventoryProductProvider;
use Zandu\Modules\Inventory\Application\Contract\{ConsumeStockForSale,InventoryStockConsumer,StockConsumptionResult};
use Zandu\Modules\Sales\Domain\Sale;

final readonly class InventoryConsumptionService
{
    public function __construct(private InventoryStockConsumer $consumer, private InventoryProductProvider $products) {}

    public function consume(Sale $sale): ?StockConsumptionResult
    {
        $items = [];
        $descriptors = [];
        foreach ($sale->lines() as $line) {
            $key = $line->productId()->toString();
            $descriptors[$key] ??= $this->products->provide($sale->organizationId(), $line->productId());
            $product = $descriptors[$key];
            if (!$product->inventoryTracked() || 'PHYSICAL' !== $product->productType()) {
                continue;
            }
            $items[] = ['productId' => $line->productId(), 'baseQuantity' => $line->baseQuantity()];
        }
        if ([] === $items) {
            return null;
        }
        return $this->consumer->consumeStockForSale(new ConsumeStockForSale($sale->organizationId(), $sale->storeId(), $sale->id(), $items));
    }
}
