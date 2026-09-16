<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Presentation\Api;

use Zandu\Modules\Purchasing\Application\GoodsReceiptCorrectionView;

final readonly class GoodsReceiptCorrectionResourceMapper
{
    public function map(GoodsReceiptCorrectionView $view): GoodsReceiptCorrectionResource
    {
        return new GoodsReceiptCorrectionResource($view->id, $view->goodsReceiptId, $view->reason, $view->status, $view->lines, $view->createdAt, $view->postedAt, $view->version);
    }
}
