<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Domain\ProductPrice;

use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;

final readonly class ProductPriceTarget
{
    public function __construct(
        private OrganizationId $organizationId,
        private ProductId $productId,
        private ProductPackagingId $packagingId,
    ) {}

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }
    public function productId(): ProductId
    {
        return $this->productId;
    }
    public function packagingId(): ProductPackagingId
    {
        return $this->packagingId;
    }
}
