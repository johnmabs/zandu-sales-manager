<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\ConfirmPurchaseOrder;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\PurchaseOrderId;

final readonly class ConfirmPurchaseOrder
{
    public function __construct(public PurchaseOrderId $purchaseOrderId, public ActorContext $actorContext) {}
}
