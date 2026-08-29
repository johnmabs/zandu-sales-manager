<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\ConfirmPurchaseOrder;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Purchasing\Application\PurchaseOrderConfirmationValidator;
use Zandu\Modules\Purchasing\Application\TenantPurchaseOrderLoader;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrder;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrderRepository;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class ConfirmPurchaseOrderHandler
{
    public function __construct(
        private TenantPurchaseOrderLoader $loader,
        private PurchaseOrderConfirmationValidator $validator,
        private PurchaseOrderRepository $purchaseOrders,
        private Clock $clock,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
    ) {}

    public function __invoke(ConfirmPurchaseOrder $command): PurchaseOrder
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): PurchaseOrder {
            $order = $this->loader->get($command->purchaseOrderId, $command->actorContext);
            $this->authorization->authorize($command->actorContext, PermissionCode::PurchaseOrderConfirm, ResourceScope::store($organizationId, $order->destinationStoreId()));
            $this->validator->validate($order, $command->actorContext);
            $order->confirm($command->actorContext->actorId(), $this->clock->now());
            $this->purchaseOrders->save($order);

            return $order;
        });
    }
}
