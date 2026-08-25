<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application\Contract;

use Zandu\SharedKernel\Decimal\Decimal;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;

final readonly class SaleablePackagingSnapshot
{
    public function __construct(
        private ProductId $productId,
        private ProductPackagingId $packagingId,
        private Decimal $conversionFactor,
        private int $sourceVersion,
    ) {}

    public function productId(): ProductId
    {
        return $this->productId;
    }
    public function packagingId(): ProductPackagingId
    {
        return $this->packagingId;
    }
    public function conversionFactor(): Decimal
    {
        return $this->conversionFactor;
    }
    public function sourceVersion(): int
    {
        return $this->sourceVersion;
    }
}
