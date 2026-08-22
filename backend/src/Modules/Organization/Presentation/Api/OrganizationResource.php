<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Presentation\Api;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;

#[ApiResource(operations: [
    new Post(name: 'organization_create', uriTemplate: '/organizations', input: OrganizationInput::class, processor: OrganizationProcessor::class),
    new Get(name: 'organization_get', uriTemplate: '/organizations/{id}', provider: OrganizationProvider::class),
    new Patch(name: 'organization_update', uriTemplate: '/organizations/{id}', read: false, input: OrganizationInput::class, processor: OrganizationProcessor::class),
    new Post(name: 'organization_suspend', uriTemplate: '/organizations/{id}/suspend', read: false, input: false, processor: OrganizationProcessor::class),
    new Post(name: 'organization_reactivate', uriTemplate: '/organizations/{id}/reactivate', read: false, input: false, processor: OrganizationProcessor::class),
    new Post(name: 'organization_closure_request', uriTemplate: '/organizations/{id}/closure-request', read: false, input: false, processor: OrganizationProcessor::class),
])]
final readonly class OrganizationResource
{
    public function __construct(
        public string $id,
        public string $name,
        public string $status,
        public string $countryCode,
        public string $defaultCurrency,
        public string $defaultTimeZone,
        public string $defaultLocale,
        public string $updatedAt,
        public int $version,
    ) {}
}
