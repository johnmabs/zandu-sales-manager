<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Domain\Product;

use Zandu\SharedKernel\Identity\CategoryId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;

interface ProductRepository
{
    public function save(Product $product): void;

    /** @throws ProductNotFound */
    public function get(OrganizationId $organizationId, ProductId $productId): Product;

    public function find(OrganizationId $organizationId, ProductId $productId): ?Product;

    public function findByCode(OrganizationId $organizationId, ProductCode $productCode): ?Product;

    /** @return list<Product> */
    public function findAll(
        OrganizationId $organizationId,
        ?ProductStatus $status = null,
        ?ProductType $type = null,
        ?CategoryId $categoryId = null,
        ?ProductCode $productCode = null,
        ?string $search = null,
    ): array;
}
