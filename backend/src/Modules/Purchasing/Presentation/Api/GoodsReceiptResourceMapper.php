<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Presentation\Api;

use Zandu\Modules\Purchasing\Application\GoodsReceiptView;

final readonly class GoodsReceiptResourceMapper
{
    public function map(GoodsReceiptView $view): GoodsReceiptResource
    {
        return new GoodsReceiptResource($view->id, $view->storeId, $view->supplierId, $view->purchaseOrderId, $view->number, $view->status, $view->supplierDeliveryNote, $view->notes, $view->lines, $view->createdAt, $view->postedAt, $view->cancelledAt, $view->version);
    }
}
