<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\CreatePurchaseReturn;

use Zandu\SharedKernel\Identity\GoodsReceiptLineId;
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class PurchaseReturnInput
{
    public function __construct(
        public GoodsReceiptLineId $goodsReceiptLineId,
        public Quantity $baseQuantity,
    ) {}
}
