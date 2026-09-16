<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Presentation\Api;

use Zandu\Modules\Purchasing\Application\PurchaseOrderView;

final readonly class PurchaseOrderResourceMapper
{
    public function map(PurchaseOrderView $view): PurchaseOrderResource
    {
        return new PurchaseOrderResource($view->id, $view->destinationStoreId, $view->supplierId, $view->number, $view->status, $view->currency, $view->expectedTotal, $view->lines, $view->createdAt, $view->confirmedAt, $view->closedAt, $view->closedReason, $view->cancelledAt, $view->version);
    }
}
