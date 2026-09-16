<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Domain\PurchaseOrder;

use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\PurchaseOrderId;
use Zandu\SharedKernel\Identity\StoreId;

interface PurchaseOrderRepository
{
    public function save(PurchaseOrder $purchaseOrder): void;

    /** @throws PurchaseOrderNotFound */
    public function get(OrganizationId $organizationId, PurchaseOrderId $purchaseOrderId): PurchaseOrder;

    public function getForUpdate(OrganizationId $organizationId, PurchaseOrderId $purchaseOrderId): PurchaseOrder;

    public function find(OrganizationId $organizationId, PurchaseOrderId $purchaseOrderId): ?PurchaseOrder;

    /** @return list<PurchaseOrder> */
    public function findAll(OrganizationId $organizationId): array;

    public function hasOpenForStore(OrganizationId $organizationId, StoreId $storeId): bool;
}
