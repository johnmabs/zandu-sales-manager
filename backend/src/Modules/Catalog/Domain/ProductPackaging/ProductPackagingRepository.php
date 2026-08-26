<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Domain\ProductPackaging;

use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;

interface ProductPackagingRepository
{
    public function save(ProductPackaging $packaging): void;
    public function get(OrganizationId $organizationId, ProductPackagingId $id): ProductPackaging;
    public function find(OrganizationId $organizationId, ProductPackagingId $id): ?ProductPackaging;
    public function findByCode(OrganizationId $organizationId, ProductId $productId, ProductPackagingCode $code): ?ProductPackaging;
    public function findBase(OrganizationId $organizationId, ProductId $productId): ?ProductPackaging;

    /** @return list<ProductPackaging> */
    public function findAll(OrganizationId $organizationId, ProductId $productId): array;
}
