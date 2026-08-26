<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Domain\ProductPrice;

use DateTimeImmutable;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;
use Zandu\SharedKernel\Identity\ProductPriceId;

interface ProductPriceRepository
{
    public function save(ProductPrice $productPrice): void;

    /** @throws ProductPriceNotFound */
    public function get(OrganizationId $organizationId, ProductPriceId $id): ProductPrice;

    public function find(OrganizationId $organizationId, ProductPriceId $id): ?ProductPrice;

    public function findEffective(
        OrganizationId $organizationId,
        ProductId $productId,
        ProductPackagingId $packagingId,
        DateTimeImmutable $businessInstant,
    ): ?ProductPrice;
    /** @return list<ProductPrice> */
    public function findAll(OrganizationId $organizationId): array;
}
