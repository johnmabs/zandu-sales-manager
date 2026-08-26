<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Presentation\Api;

use Zandu\Modules\Catalog\Application\ProductView;

final readonly class ProductResourceFactory
{
    public function fromView(ProductView $product): ProductResource
    {
        return new ProductResource(
            $product->id,
            $product->organizationId,
            $product->productCode,
            $product->name,
            $product->description,
            $product->status,
            $product->type,
            $product->baseUnitId,
            $product->inventoryTracked,
            $product->taxCategoryId,
            $product->categoryId,
            $product->createdAt,
            $product->activatedAt,
            $product->updatedAt,
            $product->version,
        );
    }
}
