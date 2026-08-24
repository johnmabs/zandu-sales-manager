<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Presentation\Api;

use Zandu\Modules\IdentityAccess\Application\MembershipView;

final readonly class MembershipResourceFactory
{
    public function fromView(MembershipView $membership): MembershipResource
    {
        return new MembershipResource(
            $membership->id,
            $membership->organizationId,
            $membership->userId,
            $membership->status,
            $membership->roleAssignments,
            $membership->authorizationVersion,
            $membership->createdAt,
            $membership->updatedAt,
            $membership->version,
        );
    }
}
