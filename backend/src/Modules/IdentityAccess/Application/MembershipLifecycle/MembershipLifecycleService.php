<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application\MembershipLifecycle;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\IdentityAccess\Application\Contract\ResourceScope;
use Zandu\Modules\IdentityAccess\Domain\Access\LastOrganizationOwner;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembership;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembershipRepository;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Organization\Application\Contract\OperationalMode;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\OrganizationMembershipId;
use Zandu\SharedKernel\Tenancy\TenantTransaction;

final readonly class MembershipLifecycleService
{
    public function __construct(
        private OrganizationMembershipRepository $memberships,
        private TenantTransaction $transaction,
        private LastOrganizationOwner $lastOwner,
        private AuthorizationService $authorization,
        private OperationalGuard $operationalGuard,
    ) {}

    /** @param callable(OrganizationMembership): void $transition */
    public function execute(OrganizationMembershipId $membershipId, ActorContext $actorContext, PermissionCode $permission, callable $transition): OrganizationMembership
    {
        $organizationId = $actorContext->organizationId();
        return $this->transaction->transactional($organizationId, function () use ($membershipId, $actorContext, $permission, $transition, $organizationId): OrganizationMembership {
            $this->authorization->authorize($actorContext, $permission, ResourceScope::organization($organizationId));
            $this->operationalGuard->assertTenant($actorContext, OperationalMode::Remediation);
            $membership = $this->memberships->get($organizationId, $membershipId);
            $transition($membership);
            $this->memberships->save($membership);
            return $membership;
        });
    }

    /** @param callable(OrganizationMembership): void $transition */
    public function deactivate(OrganizationMembershipId $membershipId, ActorContext $actorContext, PermissionCode $permission, callable $transition): OrganizationMembership
    {
        return $this->execute($membershipId, $actorContext, $permission, function (OrganizationMembership $membership) use ($transition): void {
            $this->lastOwner->protectDeactivation($membership);
            $transition($membership);
        });
    }
}
