<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application;

use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrder;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrderLine;

final readonly class PurchaseOrderViewFactory
{
    public function create(PurchaseOrder $order): PurchaseOrderView
    {
        return new PurchaseOrderView(
            $order->id()->toString(),
            $order->destinationStoreId()->toString(),
            $order->supplierId()->toString(),
            $order->number()->value(),
            $order->status()->value,
            $order->currency()->code(),
            $order->expectedTotal()->amount()->toString(),
            array_map(static fn(PurchaseOrderLine $line): array => [
                'id' => $line->id()->toString(),
                'productId' => $line->productId()->toString(),
                'productPackagingId' => $line->productPackagingId()?->toString(),
                'enteredOrderedQuantity' => $line->enteredOrderedQuantity()->toString(),
                'orderedBaseQuantity' => $line->orderedBaseQuantity()->toString(),
                'unitCost' => $line->unitCost()->amount()->toString(),
                'inventoryUnitCost' => $line->inventoryUnitCost()->amount()->toString(),
                'receivedQuantity' => $line->receivedQuantity()->toString(),
            ], $order->lines()),
            $order->createdAt()->format(DATE_ATOM),
            $order->confirmedAt()?->format(DATE_ATOM),
            $order->closedAt()?->format(DATE_ATOM),
            $order->closedReason(),
            $order->cancelledAt()?->format(DATE_ATOM),
            $order->version(),
        );
    }
}
