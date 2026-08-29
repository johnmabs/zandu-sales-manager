<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\CancelPurchaseOrder;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Organization\Application\Contract\OperationalMode;
use Zandu\Modules\Purchasing\Application\TenantPurchaseOrderLoader;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrder;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrderRepository;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class CancelPurchaseOrderHandler
{
    public function __construct(
        private TenantPurchaseOrderLoader $loader,
        private PurchaseOrderRepository $purchaseOrders,
        private Clock $clock,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
        private OperationalGuard $operationalGuard,
    ) {}

    public function __invoke(CancelPurchaseOrder $command): PurchaseOrder
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): PurchaseOrder {
            $order = $this->loader->get($command->purchaseOrderId, $command->actorContext);
            $this->authorization->authorize($command->actorContext, PermissionCode::PurchaseOrderCancel, ResourceScope::store($organizationId, $order->destinationStoreId()));
            $this->operationalGuard->assertStore($command->actorContext, $order->destinationStoreId(), OperationalMode::Remediation);
            $order->cancel($command->actorContext->actorId(), $this->clock->now());
            $this->purchaseOrders->save($order);

            return $order;
        });
    }
}
