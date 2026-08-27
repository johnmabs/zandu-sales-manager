<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Domain;

use Zandu\SharedKernel\Identity\{OrganizationId,SaleId};

interface SaleRepository
{
    public function save(Sale $sale): void;

    public function get(OrganizationId $organizationId, SaleId $saleId): Sale;

    public function getForUpdate(OrganizationId $organizationId, SaleId $saleId): Sale;
}
