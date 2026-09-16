<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application;

use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceipt;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptLine;

final readonly class GoodsReceiptViewFactory
{
    public function create(GoodsReceipt $receipt): GoodsReceiptView
    {
        return new GoodsReceiptView(
            $receipt->id()->toString(),
            $receipt->storeId()->toString(),
            $receipt->supplierId()->toString(),
            $receipt->purchaseOrderId()?->toString(),
            $receipt->number()->value(),
            $receipt->status()->value,
            $receipt->supplierDeliveryNote(),
            $receipt->notes(),
            array_map(static fn(GoodsReceiptLine $line): array => [
                'id' => $line->id()->toString(),
                'productId' => $line->productId()->toString(),
                'productPackagingId' => $line->productPackagingId()?->toString(),
                'enteredReceivedQuantity' => $line->enteredReceivedQuantity()->toString(),
                'receivedBaseQuantity' => $line->receivedBaseQuantity()->toString(),
                'actualUnitCost' => $line->actualUnitCost()?->amount()->toString(),
                'inventoryUnitCost' => $line->inventoryUnitCost()->amount()->toString(),
                'currency' => $line->inventoryUnitCost()->currency()->code(),
                'purchaseOrderLineId' => $line->purchaseOrderLineId()?->toString(),
            ], $receipt->lines()),
            $receipt->createdAt()->format(DATE_ATOM),
            $receipt->postedAt()?->format(DATE_ATOM),
            $receipt->cancelledAt()?->format(DATE_ATOM),
            $receipt->version(),
        );
    }
}
