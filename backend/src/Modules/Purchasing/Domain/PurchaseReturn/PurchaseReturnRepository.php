<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Domain\PurchaseReturn;

use Zandu\SharedKernel\Identity\{GoodsReceiptId, OrganizationId, PurchaseReturnId, StoreId};
use Zandu\SharedKernel\Quantity\Quantity;

interface PurchaseReturnRepository
{
    public function save(PurchaseReturn $return): void;
    public function get(OrganizationId $organizationId, PurchaseReturnId $returnId): PurchaseReturn;
    public function getForUpdate(OrganizationId $organizationId, PurchaseReturnId $returnId): PurchaseReturn;
    public function find(OrganizationId $organizationId, PurchaseReturnId $returnId): ?PurchaseReturn;
    /** @return array<string, Quantity> keyed by ProductId */
    public function shippedQuantityByProduct(OrganizationId $organizationId, GoodsReceiptId $goodsReceiptId): array;
    public function hasOpenForStore(OrganizationId $organizationId, StoreId $storeId): bool;
}
