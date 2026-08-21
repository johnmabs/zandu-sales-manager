<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application\SuspendOrganizationMembership;

use Zandu\Modules\IdentityAccess\Application\MembershipLifecycle\MembershipLifecycleService;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembership;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Time\Clock;

final readonly class SuspendOrganizationMembershipHandler
{
    public function __construct(private MembershipLifecycleService $lifecycle, private Clock $clock) {}
    public function __invoke(SuspendOrganizationMembership $command): OrganizationMembership
    {
        return $this->lifecycle->deactivate(
            $command->membershipId,
            $command->actorContext,
            PermissionCode::MemberSuspend,
            fn(OrganizationMembership $membership) => $membership->suspend($command->actorContext->actorId(), $this->clock->now()),
        );
    }
}
