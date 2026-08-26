<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application;

use LogicException;
use Zandu\Modules\Catalog\Application\Contract\InventoryProductDescriptor;
use Zandu\Modules\Catalog\Application\Contract\InventoryProductProvider;
use Zandu\Modules\Catalog\Domain\Product\ProductRepository;
use Zandu\Modules\Catalog\Domain\ProductPackaging\ProductPackagingRepository;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;

final readonly class RepositoryInventoryProductProvider implements InventoryProductProvider
{
    public function __construct(
        private ProductRepository $products,
        private ProductPackagingRepository $packagings,
    ) {}

    public function provide(OrganizationId $organizationId, ProductId $productId): InventoryProductDescriptor
    {
        $product = $this->products->get($organizationId, $productId);
        $basePackaging = $this->packagings->findBase($organizationId, $productId);

        if (null === $basePackaging) {
            throw new LogicException('An inventory product must have a base packaging.');
        }

        return new InventoryProductDescriptor(
            $productId,
            $product->inventoryTracked(),
            $product->type()->value,
            $product->baseUnitId(),
            $basePackaging->precision()->value(),
        );
    }
}
