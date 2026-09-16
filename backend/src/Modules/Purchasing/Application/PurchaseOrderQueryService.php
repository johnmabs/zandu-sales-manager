<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrder;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrderRepository;
use Zandu\SharedKernel\Access\AuthorizationDenied;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\PurchaseOrderId;

final readonly class PurchaseOrderQueryService
{
    public function __construct(private PurchaseOrderRepository $orders, private AuthorizationService $authorization, private PurchaseOrderViewFactory $views) {}

    public function get(ActorContext $actor, PurchaseOrderId $id): PurchaseOrderView
    {
        $order = $this->orders->get($actor->organizationId(), $id);
        $this->authorize($actor, $order);

        return $this->views->create($order);
    }

    /** @return list<PurchaseOrderView> */
    public function list(ActorContext $actor): array
    {
        $views = [];
        foreach ($this->orders->findAll($actor->organizationId()) as $order) {
            try {
                $this->authorize($actor, $order);
            } catch (AuthorizationDenied) {
                continue;
            }
            $views[] = $this->views->create($order);
        }

        return $views;
    }

    private function authorize(ActorContext $actor, PurchaseOrder $order): void
    {
        $this->authorization->authorize($actor, PermissionCode::PurchaseOrderRead, ResourceScope::store($actor->organizationId(), $order->destinationStoreId()));
    }
}
