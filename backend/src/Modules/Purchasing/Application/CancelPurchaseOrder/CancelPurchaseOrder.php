<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\CancelPurchaseOrder;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\PurchaseOrderId;

final readonly class CancelPurchaseOrder
{
    public function __construct(public PurchaseOrderId $purchaseOrderId, public ActorContext $actorContext) {}
}
