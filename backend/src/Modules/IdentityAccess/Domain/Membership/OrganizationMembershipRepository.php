<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Domain\Membership;

use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\UserId;

interface OrganizationMembershipRepository
{
    public function save(OrganizationMembership $membership): void;
    public function findByUser(OrganizationId $organizationId, UserId $userId): ?OrganizationMembership;
}
