<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\AddPurchaseOrderLine;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;
use Zandu\SharedKernel\Identity\PurchaseOrderId;
use Zandu\SharedKernel\Money\Money;
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class AddPurchaseOrderLine
{
    public function __construct(
        public PurchaseOrderId $purchaseOrderId,
        public ProductId $productId,
        public ?ProductPackagingId $productPackagingId,
        public Quantity $enteredQuantity,
        public Money $unitCost,
        public ActorContext $actorContext,
    ) {}
}
