<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Domain;

use Zandu\SharedKernel\Identity\{OrganizationId, SaleId, SaleLineId};

interface SaleLineCostSnapshotRepository
{
    public function append(SaleLineCostSnapshot $snapshot): void;

    public function findBySaleLine(OrganizationId $organizationId, SaleLineId $saleLineId): ?SaleLineCostSnapshot;

    /** @return list<SaleLineCostSnapshot> */
    public function findBySale(OrganizationId $organizationId, SaleId $saleId): array;
}
