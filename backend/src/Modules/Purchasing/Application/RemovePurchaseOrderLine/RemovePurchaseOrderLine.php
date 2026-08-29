<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\RemovePurchaseOrderLine;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\PurchaseOrderId;
use Zandu\SharedKernel\Identity\PurchaseOrderLineId;

final readonly class RemovePurchaseOrderLine
{
    public function __construct(
        public PurchaseOrderId $purchaseOrderId,
        public PurchaseOrderLineId $lineId,
        public ActorContext $actorContext,
    ) {}
}
