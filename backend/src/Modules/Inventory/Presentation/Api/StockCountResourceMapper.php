<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Presentation\Api;

use Zandu\Modules\Inventory\Application\StockCountView;

final readonly class StockCountResourceMapper
{
    public function map(StockCountView $view): StockCountResource
    {
        return new StockCountResource(
            $view->id,
            $view->storeId,
            $view->status,
            $view->mode,
            $view->scopeType,
            $view->requestedProductIds,
            $view->lines,
            $view->totalLineCount,
            $view->countedLineCount,
            $view->reconciledLineCount,
            $view->createdAt,
            $view->startedAt,
            $view->finalizationStartedAt,
            $view->completedAt,
            $view->cancelledAt,
            $view->version,
        );
    }
}
