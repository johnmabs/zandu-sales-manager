<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Domain\Access;

use LogicException;
use Zandu\Modules\IdentityAccess\Domain\Membership\MembershipStatus;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembership;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembershipRepository;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\RoleId;

final readonly class LastOrganizationOwner
{
    public function __construct(
        private OrganizationMembershipRepository $memberships,
        private SystemRoleCatalog $systemRoles,
    ) {}

    public function protectDeactivation(OrganizationMembership $membership): void
    {
        if (!$membership->hasRoleId($this->systemRoles->organizationOwnerRoleId())) {
            return;
        }

        if (1 >= $this->memberships->countActiveWithRoleForUpdate($membership->organizationId(), $this->systemRoles->organizationOwnerRoleId())) {
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
        if (null === $membership || MembershipStatus::Active !== $membership->status() || !$membership->hasRoleId($this->systemRoles->organizationOwnerRoleId())) {
            throw new LogicException('An active organization owner is required.');
        }
    }

    public function protectAssignmentChange(OrganizationMembership $membership, RoleId $roleId, ActorContext $actorContext, bool $removing): void
    {
        if (!$this->systemRoles->isOrganizationOwner($roleId)) {
            return;
        }

        $this->assertActiveOwner($actorContext);
        if ($removing) {
            $this->protectDeactivation($membership);
        }
    }
}
