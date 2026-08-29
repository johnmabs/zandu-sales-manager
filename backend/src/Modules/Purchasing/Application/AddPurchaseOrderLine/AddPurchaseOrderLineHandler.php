<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\AddPurchaseOrderLine;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Purchasing\Application\PurchaseOrderLineFactory;
use Zandu\Modules\Purchasing\Application\TenantPurchaseOrderLoader;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrder;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrderRepository;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Tenancy\TenantTransaction;

final readonly class AddPurchaseOrderLineHandler
{
    public function __construct(
        private TenantPurchaseOrderLoader $loader,
        private PurchaseOrderLineFactory $lineFactory,
        private PurchaseOrderRepository $purchaseOrders,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
        private OperationalGuard $operationalGuard,
    ) {}

    public function __invoke(AddPurchaseOrderLine $command): PurchaseOrder
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): PurchaseOrder {
            $order = $this->loader->get($command->purchaseOrderId, $command->actorContext);
            $this->authorization->authorize($command->actorContext, PermissionCode::PurchaseOrderUpdateDraft, ResourceScope::store($organizationId, $order->destinationStoreId()));
            $this->operationalGuard->assertStore($command->actorContext, $order->destinationStoreId());
            $order->addLine($this->lineFactory->create($order, $command->productId, $command->productPackagingId, $command->enteredQuantity, $command->unitCost));
            $this->purchaseOrders->save($order);

            return $order;
        });
    }
}
