<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application;

final readonly class ProductPackagingView
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $productId,
        public bool $base,
        public string $code,
        public string $name,
        public string $unitId,
        public string $conversionFactor,
        public int $precision,
        public string $minimumQuantity,
        public string $quantityIncrement,
        public bool $allowedForSale,
        public bool $allowedForPurchase,
        public string $status,
        public string $createdAt,
        public ?string $updatedAt,
        public int $version,
    ) {}
}
