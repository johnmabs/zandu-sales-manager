<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Presentation\Api;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;

#[ApiResource(operations: [
    new GetCollection(name: 'member_list', uriTemplate: '/members', provider: MembershipProvider::class),
    new Get(name: 'member_get', uriTemplate: '/members/{id}', provider: MembershipProvider::class),
    new Post(name: 'member_suspend', uriTemplate: '/members/{id}/suspend', read: false, input: false, processor: MembershipProcessor::class),
    new Post(name: 'member_reactivate', uriTemplate: '/members/{id}/reactivate', read: false, input: false, processor: MembershipProcessor::class),
    new Post(name: 'member_revoke', uriTemplate: '/members/{id}/revoke', read: false, input: false, processor: MembershipProcessor::class),
    new Post(name: 'member_role_assignment_create', uriTemplate: '/members/{id}/role-assignments', read: false, input: RoleAssignmentInput::class, processor: RoleAssignmentProcessor::class),
    new Delete(name: 'member_role_assignment_delete', uriTemplate: '/members/{id}/role-assignments/{assignmentId}', status: 200, read: false, output: MembershipResource::class, processor: RoleAssignmentProcessor::class),
])]
final readonly class MembershipResource
{
    /** @param non-empty-list<array{assignmentId: string, roleId: string, scopeType: string, storeIds: list<string>, assignedAt: string, expiresAt: ?string}> $roleAssignments */
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
