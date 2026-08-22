<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application;

final readonly class InvitationView
{
    /** @param non-empty-list<array{roleCode: string, storeIds: list<string>}> $roleAssignments */
    public function __construct(
        public string $id,
        public string $organizationId,
        public string $email,
        public string $status,
        public string $expiresAt,
        public array $roleAssignments,
        public ?string $acceptedAt,
        public int $version,
    ) {}
}
