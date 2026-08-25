<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application;

use Zandu\Modules\Catalog\Domain\Product\Product;
use Zandu\Modules\Catalog\Domain\Product\ProductRepository;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\ProductId;

final readonly class TenantProductLoader
{
    public function __construct(private ProductRepository $products) {}

    public function get(ProductId $productId, ActorContext $actorContext): Product
    {
        return $this->products->get($actorContext->organizationId(), $productId);
    }
}
