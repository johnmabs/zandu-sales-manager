<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Presentation\Api;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;

#[ApiResource(operations: [
    new GetCollection(name: 'product_list', uriTemplate: '/products', provider: ProductProvider::class, extraProperties: ['zandu_cursor_pagination' => true, 'zandu_cursor_direction' => 'asc']),
    new Post(name: 'product_create', uriTemplate: '/products', input: ProductCreateInput::class, processor: ProductProcessor::class),
    new Get(name: 'product_get', uriTemplate: '/products/{id}', provider: ProductProvider::class),
    new Patch(name: 'product_update', uriTemplate: '/products/{id}', read: false, input: ProductUpdateInput::class, processor: ProductProcessor::class),
    new Post(name: 'product_activate', uriTemplate: '/products/{id}/activate', read: false, input: false, processor: ProductProcessor::class),
    new Post(name: 'product_deactivate', uriTemplate: '/products/{id}/deactivate', read: false, input: false, processor: ProductProcessor::class),
    new Post(name: 'product_reactivate', uriTemplate: '/products/{id}/reactivate', read: false, input: false, processor: ProductProcessor::class),
    new Post(name: 'product_archive', uriTemplate: '/products/{id}/archive', read: false, input: false, processor: ProductProcessor::class),
])]
final readonly class ProductResource
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $productCode,
        public string $name,
        public ?string $description,
        public string $status,
        public string $type,
        public string $baseUnitId,
        public bool $inventoryTracked,
        public ?string $taxCategoryId,
        public ?string $categoryId,
        public string $createdAt,
        public ?string $activatedAt,
        public ?string $updatedAt,
        public int $version,
    ) {}
}
