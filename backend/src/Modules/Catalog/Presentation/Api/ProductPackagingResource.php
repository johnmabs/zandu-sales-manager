<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Presentation\Api;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;

#[ApiResource(operations: [
    new GetCollection(name: 'packaging_list', uriTemplate: '/products/{productId}/packagings', provider: ProductPackagingProvider::class),
    new Get(name: 'packaging_get', uriTemplate: '/products/{productId}/packagings/{id}', provider: ProductPackagingProvider::class),
])]
final readonly class ProductPackagingResource
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
