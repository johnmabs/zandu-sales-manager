<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application\MembershipLifecycle;

use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembership;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembershipRepository;
use Zandu\Modules\Organization\Application\Contract\MembershipManagementPolicy;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\OrganizationMembershipId;
use Zandu\SharedKernel\Tenancy\TenantTransaction;

final readonly class MembershipLifecycleService
{
    public function __construct(
        private OrganizationMembershipRepository $memberships,
        private MembershipManagementPolicy $policy,
        private TenantTransaction $transaction,
    ) {}

    /** @param callable(OrganizationMembership): void $transition */
    public function execute(OrganizationMembershipId $membershipId, ActorContext $actorContext, callable $transition): OrganizationMembership
    {
        $organizationId = $actorContext->organizationId();
        return $this->transaction->transactional($organizationId, function () use ($membershipId, $actorContext, $transition, $organizationId): OrganizationMembership {
            $this->policy->assertCanManage($actorContext);
            $membership = $this->memberships->get($organizationId, $membershipId);
            $transition($membership);
            $this->memberships->save($membership);
            return $membership;
        });
    }
}
