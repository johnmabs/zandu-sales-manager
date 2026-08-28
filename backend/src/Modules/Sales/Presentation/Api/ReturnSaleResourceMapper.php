<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Presentation\Api;

use Zandu\Modules\Sales\Application\ReturnSaleView;

final readonly class ReturnSaleResourceMapper
{
    public function map(ReturnSaleView $view): ReturnSaleResource
    {
        return new ReturnSaleResource($view->id, $view->saleId, $view->storeId, $view->status, $view->reason, $view->lines, $view->businessDate, $view->completedAt, $view->cancelledAt, $view->version);
    }
}
