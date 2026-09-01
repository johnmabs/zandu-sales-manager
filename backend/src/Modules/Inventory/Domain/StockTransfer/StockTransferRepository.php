<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Domain\StockTransfer;

use Zandu\SharedKernel\Identity\{OrganizationId, StockTransferId, StoreId};

interface StockTransferRepository
{
    public function save(StockTransfer $transfer): void;
    public function get(OrganizationId $organizationId, StockTransferId $transferId): StockTransfer;
    public function getForUpdate(OrganizationId $organizationId, StockTransferId $transferId): StockTransfer;
    public function find(OrganizationId $organizationId, StockTransferId $transferId): ?StockTransfer;
    /** @return list<StockTransfer> */
    public function findAll(OrganizationId $organizationId): array;
    public function hasInTransitForStore(OrganizationId $organizationId, StoreId $storeId): bool;
}
