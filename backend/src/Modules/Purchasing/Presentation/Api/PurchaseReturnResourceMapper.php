<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Presentation\Api;

use Zandu\Modules\Purchasing\Application\PurchaseReturnView;

final readonly class PurchaseReturnResourceMapper
{
    public function map(PurchaseReturnView $view): PurchaseReturnResource
    {
        return new PurchaseReturnResource(
            $view->id,
            $view->sourceStoreId,
            $view->supplierId,
            $view->goodsReceiptId,
            $view->purchaseOrderId,
            $view->status,
            $view->reason,
            $view->lines,
            $view->createdAt,
            $view->shippedAt,
            $view->cancelledAt,
            $view->version,
        );
    }
}
