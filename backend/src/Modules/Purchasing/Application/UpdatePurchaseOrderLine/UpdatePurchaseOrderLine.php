<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\UpdatePurchaseOrderLine;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;
use Zandu\SharedKernel\Identity\PurchaseOrderId;
use Zandu\SharedKernel\Identity\PurchaseOrderLineId;
use Zandu\SharedKernel\Money\Money;
use Zandu\SharedKernel\Quantity\Quantity;
use Zandu\SharedKernel\Versioning\ExpectedVersion;

final readonly class UpdatePurchaseOrderLine
{
    public function __construct(
        public PurchaseOrderId $purchaseOrderId,
        public PurchaseOrderLineId $lineId,
        public ProductId $productId,
        public ?ProductPackagingId $productPackagingId,
        public Quantity $enteredQuantity,
        public Money $unitCost,
        public ExpectedVersion $expectedVersion,
        public ActorContext $actorContext,
    ) {}
}
