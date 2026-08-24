<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application;

final readonly class MembershipView
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
