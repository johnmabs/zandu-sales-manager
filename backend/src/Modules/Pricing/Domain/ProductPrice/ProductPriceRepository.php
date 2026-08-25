<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Domain\ProductPrice;

use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductPriceId;

interface ProductPriceRepository
{
    public function save(ProductPrice $productPrice): void;

    /** @throws ProductPriceNotFound */
    public function get(OrganizationId $organizationId, ProductPriceId $id): ProductPrice;

    public function find(OrganizationId $organizationId, ProductPriceId $id): ?ProductPrice;
}
