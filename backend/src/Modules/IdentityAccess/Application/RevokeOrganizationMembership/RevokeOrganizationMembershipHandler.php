<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application\RevokeOrganizationMembership;

use Zandu\Modules\IdentityAccess\Application\MembershipLifecycle\MembershipLifecycleService;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembership;
use Zandu\SharedKernel\Time\Clock;

final readonly class RevokeOrganizationMembershipHandler
{
    public function __construct(private MembershipLifecycleService $lifecycle, private Clock $clock) {}
    public function __invoke(RevokeOrganizationMembership $command): OrganizationMembership
    {
        return $this->lifecycle->deactivate(
            $command->membershipId,
            $command->actorContext,
            fn(OrganizationMembership $membership) => $membership->revoke($command->actorContext->actorId(), $this->clock->now()),
        );
    }
}
