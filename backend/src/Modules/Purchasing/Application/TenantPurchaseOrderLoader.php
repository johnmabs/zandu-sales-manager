<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application;

use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrder;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrderRepository;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\PurchaseOrderId;

final readonly class TenantPurchaseOrderLoader
{
    public function __construct(private PurchaseOrderRepository $purchaseOrders) {}

    public function get(PurchaseOrderId $purchaseOrderId, ActorContext $actorContext): PurchaseOrder
    {
        return $this->purchaseOrders->get($actorContext->organizationId(), $purchaseOrderId);
    }
}
