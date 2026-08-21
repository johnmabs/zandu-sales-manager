<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Domain\Access;

use LogicException;
use Zandu\Modules\IdentityAccess\Domain\Membership\MembershipStatus;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembership;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembershipRepository;
use Zandu\SharedKernel\Context\ActorContext;

final readonly class LastOrganizationOwner
{
    public function __construct(private OrganizationMembershipRepository $memberships) {}

    public function protectDeactivation(OrganizationMembership $membership): void
    {
        if (!$membership->hasRole(RoleCode::organizationOwner())) {
            return;
        }

        if (1 >= $this->memberships->countActiveWithRoleForUpdate($membership->organizationId(), RoleCode::organizationOwner())) {
            throw new LogicException('The last active organization owner cannot be suspended or revoked.');
        }
    }

    public function assertActiveOwner(ActorContext $actorContext): void
    {
        $userId = $actorContext->userId();
        if (null === $userId) {
            throw new LogicException('An active organization owner is required.');
        }

        $membership = $this->memberships->findByUser($actorContext->organizationId(), $userId);
        if (null === $membership || MembershipStatus::Active !== $membership->status() || !$membership->hasRole(RoleCode::organizationOwner())) {
            throw new LogicException('An active organization owner is required.');
        }
    }
}
