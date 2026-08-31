<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\AddPurchaseReturnLine;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\GoodsReceiptLineId;
use Zandu\SharedKernel\Identity\PurchaseReturnId;
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class AddPurchaseReturnLine
{
    public function __construct(
        public PurchaseReturnId $purchaseReturnId,
        public GoodsReceiptLineId $goodsReceiptLineId,
        public Quantity $baseQuantity,
        public ActorContext $actorContext,
    ) {}
}
