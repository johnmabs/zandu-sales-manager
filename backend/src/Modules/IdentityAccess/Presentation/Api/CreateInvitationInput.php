<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Presentation\Api;

final readonly class CreateInvitationInput
{
    /** @param non-empty-list<IntendedRoleAssignmentInput> $roleAssignments */
    public function __construct(
        public string $email,
        public array $roleAssignments,
        public ?string $expiresAt = null,
    ) {}
}
