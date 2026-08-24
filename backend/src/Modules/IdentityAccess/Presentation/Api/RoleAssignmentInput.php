<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Presentation\Api;

final readonly class RoleAssignmentInput
{
    /** @param list<string> $storeIds */
    public function __construct(
        public string $roleId,
        public string $scopeType,
        public array $storeIds = [],
        public ?string $expiresAt = null,
    ) {}
}
