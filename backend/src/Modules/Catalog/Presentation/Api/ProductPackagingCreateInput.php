<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Presentation\Api;

final readonly class ProductPackagingCreateInput
{
    public function __construct(
        public string $code,
        public string $name,
        public string $unitId,
        public string $conversionFactor,
        public int $precision,
        public string $minimumQuantity,
        public string $quantityIncrement,
        public bool $allowedForSale,
        public bool $allowedForPurchase,
    ) {}
}
