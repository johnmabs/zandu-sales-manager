<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application;

use Zandu\Modules\Catalog\Application\Contract\PurchasableProductSnapshotProvider;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrder;
use Zandu\Modules\Purchasing\Domain\PurchasingRuleViolation;
use Zandu\SharedKernel\Context\ActorContext;

final readonly class PurchaseOrderConfirmationValidator
{
    public function __construct(
        private TenantSupplierLoader $suppliers,
        private PurchasableProductSnapshotProvider $catalog,
        private OperationalGuard $operationalGuard,
    ) {}

    public function validate(PurchaseOrder $order, ActorContext $actorContext): void
    {
        $this->operationalGuard->assertStore($actorContext, $order->destinationStoreId());
        $this->suppliers->get($order->supplierId(), $actorContext)->ensureUsable();
        foreach ($order->lines() as $line) {
            $snapshot = $this->catalog->provide($order->organizationId(), $line->productId(), $line->productPackagingId());
            if (!$snapshot->conversionFactor->equals($line->conversionFactorSnapshot()->value())) {
                throw PurchasingRuleViolation::with('PURCHASE_ORDER_SNAPSHOT_STALE', 'Purchase order conversion snapshot no longer matches Catalog.');
            }
        }
    }
}
