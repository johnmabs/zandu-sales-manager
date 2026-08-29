<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Domain\GoodsReceiptCorrection;

enum GoodsReceiptCorrectionStatus: string
{
    case Draft = 'DRAFT';
    case Posted = 'POSTED';
}
