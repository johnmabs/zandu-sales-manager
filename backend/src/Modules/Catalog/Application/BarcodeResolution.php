<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application;

use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;

final readonly class BarcodeResolution
{
    public function __construct(
        private ProductId $productId,
        private ProductPackagingId $packagingId,
    ) {}

    public function productId(): ProductId
    {
        return $this->productId;
    }

    public function packagingId(): ProductPackagingId
    {
        return $this->packagingId;
    }
}
