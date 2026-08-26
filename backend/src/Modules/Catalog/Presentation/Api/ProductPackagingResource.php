<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Presentation\Api;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;

#[ApiResource(operations: [
    new GetCollection(name: 'packaging_list', uriTemplate: '/products/{productId}/packagings', provider: ProductPackagingProvider::class),
    new Get(name: 'packaging_get', uriTemplate: '/products/{productId}/packagings/{id}', provider: ProductPackagingProvider::class),
    new Post(name: 'packaging_create', uriTemplate: '/products/{productId}/packagings', input: ProductPackagingCreateInput::class, processor: ProductPackagingProcessor::class),
    new Patch(name: 'packaging_update', uriTemplate: '/products/{productId}/packagings/{id}', read: false, input: ProductPackagingUpdateInput::class, processor: ProductPackagingProcessor::class),
    new Post(name: 'packaging_deactivate', uriTemplate: '/products/{productId}/packagings/{id}/deactivate', read: false, input: false, processor: ProductPackagingProcessor::class),
    new Post(name: 'packaging_archive', uriTemplate: '/products/{productId}/packagings/{id}/archive', read: false, input: false, processor: ProductPackagingProcessor::class),
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
