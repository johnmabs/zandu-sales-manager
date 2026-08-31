<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Presentation\Api;

final class PurchaseReturnLineInput
{
    public string $goodsReceiptLineId;
    public string $baseQuantity;
}
