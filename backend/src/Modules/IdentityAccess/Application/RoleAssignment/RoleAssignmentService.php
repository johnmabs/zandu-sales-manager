<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application\RoleAssignment;

use DateTimeImmutable;
use Zandu\Modules\IdentityAccess\Domain\Access\AccessScope;
use Zandu\Modules\IdentityAccess\Domain\Access\LastOrganizationOwner;
use Zandu\Modules\IdentityAccess\Domain\Access\RoleAssignment;
use Zandu\Modules\IdentityAccess\Domain\Access\SystemRoleCatalog;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembership;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembershipRepository;
use Zandu\Modules\Organization\Application\Contract\MembershipManagementPolicy;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\OrganizationMembershipId;
use Zandu\SharedKernel\Identity\RoleId;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class RoleAssignmentService
{
    public function __construct(
        private OrganizationMembershipRepository $memberships,
        private MembershipManagementPolicy $policy,
        private LastOrganizationOwner $lastOwner,
        private SystemRoleCatalog $systemRoles,
        private TenantTransaction $transaction,
        private Clock $clock,
    ) {}

    public function assign(
        OrganizationMembershipId $membershipId,
        RoleId $roleId,
        AccessScope $scope,
        ?DateTimeImmutable $expiresAt,
        ActorContext $actorContext,
    ): OrganizationMembership {
        return $this->transaction->transactional($actorContext->organizationId(), function () use ($membershipId, $roleId, $scope, $expiresAt, $actorContext): OrganizationMembership {
            $this->policy->assertCanManage($actorContext);
            $this->systemRoles->getById($roleId);
            $membership = $this->memberships->get($actorContext->organizationId(), $membershipId);
            $this->lastOwner->protectAssignmentChange($membership, $roleId, $actorContext, false);
            $now = $this->clock->now();
            $membership->assignRole(RoleAssignment::assign($roleId, $scope, $actorContext->actorId(), $now, $expiresAt), $actorContext->actorId(), $now);
            $this->memberships->save($membership);

            return $membership;
        });
    }

    public function remove(
        OrganizationMembershipId $membershipId,
        RoleId $roleId,
        ActorContext $actorContext,
    ): OrganizationMembership {
        return $this->transaction->transactional($actorContext->organizationId(), function () use ($membershipId, $roleId, $actorContext): OrganizationMembership {
            $this->policy->assertCanManage($actorContext);
            $this->systemRoles->getById($roleId);
            $membership = $this->memberships->get($actorContext->organizationId(), $membershipId);
            $this->lastOwner->protectAssignmentChange($membership, $roleId, $actorContext, true);
            $membership->removeRole($roleId, $actorContext->actorId(), $this->clock->now());
            $this->memberships->save($membership);

            return $membership;
        });
    }
}
