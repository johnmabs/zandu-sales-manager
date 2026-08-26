<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application;

use Zandu\Modules\Catalog\Domain\Product\Product;

final readonly class ProductViewFactory
{
    public function fromAggregate(Product $product): ProductView
    {
        return new ProductView(
            $product->id()->toString(),
            $product->organizationId()->toString(),
            $product->productCode()->value(),
            $product->name()->value(),
            $product->description(),
            $product->status()->value,
            $product->type()->value,
            $product->baseUnitId()->toString(),
            $product->inventoryTracked(),
            $product->taxCategoryId()?->toString(),
            $product->categoryId()?->toString(),
            $product->createdAt()->format(DATE_ATOM),
            $product->activatedAt()?->format(DATE_ATOM),
            $product->updatedAt()?->format(DATE_ATOM),
            $product->version(),
        );
    }
}
