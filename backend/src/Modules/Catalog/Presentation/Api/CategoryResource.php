<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Presentation\Api;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;

#[ApiResource(operations: [
    new GetCollection(name: 'category_list', uriTemplate: '/categories', provider: CategoryProvider::class),
    new Post(name: 'category_create', uriTemplate: '/categories', input: CategoryCreateInput::class, processor: CategoryProcessor::class),
    new Get(name: 'category_get', uriTemplate: '/categories/{id}', provider: CategoryProvider::class),
    new Patch(name: 'category_update', uriTemplate: '/categories/{id}', read: false, input: CategoryUpdateInput::class, processor: CategoryProcessor::class),
    new Post(name: 'category_move', uriTemplate: '/categories/{id}/move', read: false, input: CategoryMoveInput::class, processor: CategoryProcessor::class),
    new Post(name: 'category_activate', uriTemplate: '/categories/{id}/activate', read: false, input: false, processor: CategoryProcessor::class),
    new Post(name: 'category_deactivate', uriTemplate: '/categories/{id}/deactivate', read: false, input: false, processor: CategoryProcessor::class),
    new Post(name: 'category_archive', uriTemplate: '/categories/{id}/archive', read: false, input: false, processor: CategoryProcessor::class),
])]
final readonly class CategoryResource
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $name,
        public ?string $parentCategoryId,
        public string $status,
        public string $createdAt,
        public ?string $updatedAt,
        public int $version,
    ) {}
}
