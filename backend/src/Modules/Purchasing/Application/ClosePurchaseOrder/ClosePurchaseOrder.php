<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\ClosePurchaseOrder;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\PurchaseOrderId;

final readonly class ClosePurchaseOrder
{
    public function __construct(public PurchaseOrderId $purchaseOrderId, public ?string $reason, public ActorContext $actorContext) {}
}
