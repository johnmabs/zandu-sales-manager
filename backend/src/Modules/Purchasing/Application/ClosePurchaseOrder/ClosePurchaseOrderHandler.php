<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\ClosePurchaseOrder;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Organization\Application\Contract\OperationalMode;
use Zandu\Modules\Purchasing\Application\TenantPurchaseOrderLoader;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrder;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrderRepository;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrderStatus;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\SecurityAudit\ResourceReference;
use Zandu\SharedKernel\SecurityAudit\SafeAuditMetadata;
use Zandu\SharedKernel\SecurityAudit\SecurityAction;
use Zandu\SharedKernel\SecurityAudit\SecurityAuditTrail;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class ClosePurchaseOrderHandler
{
    public function __construct(
        private TenantPurchaseOrderLoader $loader,
        private PurchaseOrderRepository $purchaseOrders,
        private Clock $clock,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
        private OperationalGuard $operationalGuard,
        private SecurityAuditTrail $audit,
    ) {}

    public function __invoke(ClosePurchaseOrder $command): PurchaseOrder
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): PurchaseOrder {
            $order = $this->loader->get($command->purchaseOrderId, $command->actorContext);
            $this->authorization->authorize($command->actorContext, PermissionCode::PurchaseOrderClose, ResourceScope::store($organizationId, $order->destinationStoreId()));
            $this->operationalGuard->assertStore($command->actorContext, $order->destinationStoreId(), OperationalMode::Remediation);
            $partial = PurchaseOrderStatus::PartiallyReceived === $order->status();
            $now = $this->clock->now();
            $order->close($command->actorContext->actorId(), $now, $command->reason);
            $this->purchaseOrders->save($order);
            if ($partial) {
                $this->audit->recordSuccess(
                    $command->actorContext,
                    SecurityAction::PartialPurchaseOrderClosed,
                    ResourceReference::for('purchase_order', $order->id()),
                    SafeAuditMetadata::fromArray(['reason' => $order->closedReason()]),
                    $now,
                );
            }

            return $order;
        });
    }
}
