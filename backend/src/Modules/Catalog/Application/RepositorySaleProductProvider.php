<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application;

use Zandu\Modules\Catalog\Domain\Product\ProductRepository;
use Zandu\Modules\Catalog\Domain\ProductPackaging\{ProductPackagingNotFound,ProductPackagingRepository};
use Zandu\Modules\Catalog\Application\Contract\{SaleProductDescriptor,SaleProductProvider};
use Zandu\SharedKernel\Identity\{OrganizationId,ProductId,ProductPackagingId};
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class RepositorySaleProductProvider implements SaleProductProvider
{
    public function __construct(private ProductRepository $products, private ProductPackagingRepository $packagings) {}

    public function provide(OrganizationId $organizationId, ProductId $productId, ProductPackagingId $productPackagingId): SaleProductDescriptor
    {
        $product = $this->products->get($organizationId, $productId);
        $product->ensureCommerciallyAvailable();
        $packaging = $this->packagings->get($organizationId, $productPackagingId);
        if (!$packaging->productId()->equals($productId)) {
            throw ProductPackagingNotFound::withId($productPackagingId);
        }
        $packaging->ensureAvailableForSale();

        return new SaleProductDescriptor(
            $product->id(),
            $packaging->id(),
            $product->productCode()->value(),
            $product->name()->value(),
            $packaging->code()->value(),
            $packaging->name()->value(),
            $packaging->unitId(),
            new Quantity($packaging->conversionFactor()->value()),
            max($product->version(), $packaging->version()),
            $product->inventoryTracked(),
            $product->type()->value,
            $packaging->minimumQuantity(),
            $packaging->quantityIncrement(),
            $packaging->precision()->value(),
        );
    }
}
