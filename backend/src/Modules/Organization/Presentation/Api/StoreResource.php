<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Presentation\Api;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;

#[ApiResource(operations: [
    new GetCollection(name: 'store_list', uriTemplate: '/stores', provider: StoreProvider::class),
    new Post(name: 'store_create', uriTemplate: '/stores', input: StoreCreateInput::class, processor: StoreProcessor::class),
    new Get(name: 'store_get', uriTemplate: '/stores/{id}', provider: StoreProvider::class),
    new Patch(name: 'store_update', uriTemplate: '/stores/{id}', read: false, input: StoreUpdateInput::class, processor: StoreProcessor::class),
    new Post(name: 'store_suspend', uriTemplate: '/stores/{id}/suspend', read: false, input: false, processor: StoreProcessor::class),
    new Post(name: 'store_reactivate', uriTemplate: '/stores/{id}/reactivate', read: false, input: false, processor: StoreProcessor::class),
    new Post(name: 'store_closure_request', uriTemplate: '/stores/{id}/closure-request', read: false, input: StoreClosureRequestInput::class, output: StoreClosureResource::class, processor: StoreProcessor::class),
    new Post(name: 'store_closure_cancel', uriTemplate: '/stores/{id}/closure-request/cancel', read: false, input: false, processor: StoreProcessor::class),
])]
final readonly class StoreResource
{
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $code,
        public string $name,
        public string $status,
        public ?string $address,
        public string $timeZone,
        public string $currency,
        public string $locale,
        public string $updatedAt,
        public int $version,
    ) {}
}
