<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Domain;

use Zandu\SharedKernel\Identity\{OrganizationId, ReturnSaleId, SaleId};

interface ReturnSaleRepository
{
    public function save(ReturnSale $returnSale): void;

    public function get(OrganizationId $organizationId, ReturnSaleId $returnSaleId): ReturnSale;

    public function getForUpdate(OrganizationId $organizationId, ReturnSaleId $returnSaleId): ReturnSale;

    /** @return list<ReturnSale> */
    public function findBySale(OrganizationId $organizationId, SaleId $saleId): array;
}
