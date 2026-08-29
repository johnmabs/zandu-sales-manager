<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\CreateLinkedGoodsReceipt;

use Zandu\SharedKernel\Identity\PurchaseOrderLineId;
use Zandu\SharedKernel\Money\Money;
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class LinkedGoodsReceiptLine
{
    public function __construct(
        public PurchaseOrderLineId $purchaseOrderLineId,
        public Quantity $enteredReceivedQuantity,
        public ?Money $actualUnitCost = null,
    ) {}
}
