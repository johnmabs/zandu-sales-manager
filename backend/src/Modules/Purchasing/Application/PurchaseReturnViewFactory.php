<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application;

use Zandu\Modules\Purchasing\Domain\PurchaseReturn\PurchaseReturn;
use Zandu\Modules\Purchasing\Domain\PurchaseReturn\PurchaseReturnLine;

final readonly class PurchaseReturnViewFactory
{
    public function create(PurchaseReturn $return): PurchaseReturnView
    {
        return new PurchaseReturnView(
            $return->id()->toString(),
            $return->sourceStoreId()->toString(),
            $return->supplierId()->toString(),
            $return->goodsReceiptId()?->toString(),
            $return->purchaseOrderId()?->toString(),
            $return->status()->value,
            $return->reason(),
            array_map(static fn(PurchaseReturnLine $line): array => [
                'id' => $line->id()->toString(),
                'productId' => $line->productId()->toString(),
                'baseQuantity' => $line->baseQuantity()->toString(),
                'goodsReceiptLineId' => $line->goodsReceiptLineId()?->toString(),
            ], $return->lines()),
            $return->createdAt()->format(DATE_ATOM),
            $return->shippedAt()?->format(DATE_ATOM),
            $return->cancelledAt()?->format(DATE_ATOM),
            $return->version(),
        );
    }
}
