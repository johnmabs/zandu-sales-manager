<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Presentation\Api;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;

#[ApiResource(operations: [
    new GetCollection(name: 'member_list', uriTemplate: '/members', provider: MembershipProvider::class),
    new Get(name: 'member_get', uriTemplate: '/members/{id}', provider: MembershipProvider::class),
    new Post(name: 'member_suspend', uriTemplate: '/members/{id}/suspend', read: false, input: false, processor: MembershipProcessor::class),
    new Post(name: 'member_reactivate', uriTemplate: '/members/{id}/reactivate', read: false, input: false, processor: MembershipProcessor::class),
    new Post(name: 'member_revoke', uriTemplate: '/members/{id}/revoke', read: false, input: false, processor: MembershipProcessor::class),
])]
final readonly class MembershipResource
{
    /** @param non-empty-list<array{roleId: string, scopeType: string, storeIds: list<string>, assignedAt: string, expiresAt: ?string}> $roleAssignments */
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $userId,
        public string $status,
        public array $roleAssignments,
        public int $authorizationVersion,
        public string $createdAt,
        public string $updatedAt,
        public int $version,
    ) {}
}
