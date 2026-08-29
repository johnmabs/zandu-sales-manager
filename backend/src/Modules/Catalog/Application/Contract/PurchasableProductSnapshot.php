<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application\Contract;

use Zandu\SharedKernel\Decimal\Decimal;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;

final readonly class PurchasableProductSnapshot
{
    public function __construct(
        public ProductId $productId,
        public ?ProductPackagingId $packagingId,
        public Decimal $conversionFactor,
        public int $productVersion,
        public int $packagingVersion,
    ) {}
}
