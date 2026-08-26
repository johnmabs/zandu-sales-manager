<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application;

use Zandu\Modules\Catalog\Domain\ProductPackaging\ProductPackaging;

final readonly class ProductPackagingViewFactory
{
    public function fromAggregate(ProductPackaging $packaging): ProductPackagingView
    {
        return new ProductPackagingView(
            $packaging->id()->toString(),
            $packaging->organizationId()->toString(),
            $packaging->productId()->toString(),
            $packaging->isBase(),
            $packaging->code()->value(),
            $packaging->name()->value(),
            $packaging->unitId()->toString(),
            $packaging->conversionFactor()->toString(),
            $packaging->precision()->value(),
            $packaging->minimumQuantity()->toString(),
            $packaging->quantityIncrement()->toString(),
            $packaging->allowedForSale(),
            $packaging->allowedForPurchase(),
            $packaging->status()->value,
            $packaging->createdAt()->format(DATE_ATOM),
            $packaging->updatedAt()?->format(DATE_ATOM),
            $packaging->version(),
        );
    }
}
