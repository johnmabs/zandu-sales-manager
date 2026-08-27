<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Presentation\Api;

use Zandu\Modules\Sales\Domain\Sale;

final readonly class SaleResourceMapper
{
    public function map(Sale $sale): SaleResource
    {
        $lines = array_map(static fn($line): array => [
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
        ], $sale->lines());

        return new SaleResource(
            $sale->id()->toString(),
            $sale->storeId()->toString(),
            $sale->status()->value,
            $sale->currency(),
            $lines,
            $sale->subtotal()->amount()->toString(),
            $sale->discountTotal()->amount()->toString(),
            $sale->taxTotal()->amount()->toString(),
            $sale->total()->amount()->toString(),
            $sale->businessDate(),
            $sale->completedAt()?->format(DATE_ATOM),
            $sale->version(),
        );
    }
}
