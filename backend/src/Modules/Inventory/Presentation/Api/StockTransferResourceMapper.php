<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Presentation\Api;

use Zandu\Modules\Inventory\Application\StockTransferView;

final readonly class StockTransferResourceMapper
{
    public function map(StockTransferView $view): StockTransferResource
    {
        return new StockTransferResource($view->id, $view->sourceStoreId, $view->destinationStoreId, $view->status, $view->lines, $view->hasTransitDiscrepancy, $view->createdAt, $view->shippedAt, $view->receivedAt, $view->cancellationReason, $view->cancelledAt, $view->version);
    }
}
