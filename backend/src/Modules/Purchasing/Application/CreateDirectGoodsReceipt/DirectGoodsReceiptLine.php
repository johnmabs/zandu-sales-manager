<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\CreateDirectGoodsReceipt;

use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;
use Zandu\SharedKernel\Money\Money;
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class DirectGoodsReceiptLine
{
    public function __construct(
        public ProductId $productId,
        public ?ProductPackagingId $packagingId,
        public Quantity $enteredReceivedQuantity,
        public Money $inventoryUnitCost,
    ) {}
}
