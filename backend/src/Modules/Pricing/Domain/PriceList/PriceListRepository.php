<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Domain\PriceList;

use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\PriceListId;

interface PriceListRepository
{
    public function save(PriceList $priceList): void;

    /** @throws PriceListNotFound */
    public function get(OrganizationId $organizationId, PriceListId $id): PriceList;

    public function find(OrganizationId $organizationId, PriceListId $id): ?PriceList;

    public function findByCode(OrganizationId $organizationId, PriceListCode $code): ?PriceList;
}
