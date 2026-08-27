<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application;

use Zandu\Modules\Sales\Domain\Sale;

final readonly class SaleViewFactory
{
    public function create(Sale $sale): SaleView
    {
        return new SaleView([
            'id' => $sale->id()->toString(),
            'storeId' => $sale->storeId()->toString(),
            'status' => $sale->status()->value,
            'currency' => $sale->currency(),
            'lines' => array_map(static fn($line): array => [
                'id' => $line->id()->toString(),
                'productId' => $line->productId()->toString(),
                'productPackagingId' => $line->productPackagingId()->toString(),
                'productCode' => $line->productCodeSnapshot(),
                'productName' => $line->productNameSnapshot(),
                'packagingCode' => $line->packagingCodeSnapshot(),
                'packagingName' => $line->packagingNameSnapshot(),
                'unitId' => $line->unitIdSnapshot()->toString(),
                'quantity' => $line->enteredQuantity()->toString(),
                'conversionFactor' => $line->conversionFactorSnapshot()->toString(),
                'baseQuantity' => $line->baseQuantity()->toString(),
                'unitPrice' => $line->unitPrice()->amount()->toString(),
                'priceListId' => $line->priceListId(),
                'productPriceId' => $line->productPriceId(),
                'discountAmount' => $line->discountAmount()->amount()->toString(),
                'taxableAmount' => $line->taxableAmount()->amount()->toString(),
                'taxAmount' => $line->taxAmount()->amount()->toString(),
                'subtotal' => $line->subtotal()->amount()->toString(),
                'total' => $line->total()->amount()->toString(),
                'sourceVersions' => $line->sourceVersions(),
            ], $sale->lines()),
            'subtotal' => $sale->subtotal()->amount()->toString(),
            'discountTotal' => $sale->discountTotal()->amount()->toString(),
            'taxTotal' => $sale->taxTotal()->amount()->toString(),
            'total' => $sale->total()->amount()->toString(),
            'businessDate' => $sale->businessDate(),
            'completedAt' => $sale->completedAt()?->format(DATE_ATOM),
            'version' => $sale->version(),
        ]);
    }
}
