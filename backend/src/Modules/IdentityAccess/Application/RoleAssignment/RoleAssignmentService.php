<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application\RoleAssignment;

use DateTimeImmutable;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\IdentityAccess\Application\Contract\ResourceScope;
use Zandu\Modules\IdentityAccess\Domain\Access\AccessScope;
use Zandu\Modules\IdentityAccess\Domain\Access\LastOrganizationOwner;
use Zandu\Modules\IdentityAccess\Domain\Access\RoleAssignment;
use Zandu\Modules\IdentityAccess\Domain\Access\SystemRoleCatalog;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembership;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembershipRepository;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Organization\Application\Contract\OperationalMode;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\OrganizationMembershipId;
use Zandu\SharedKernel\Identity\RoleId;
use Zandu\SharedKernel\SecurityAudit\ResourceReference;
use Zandu\SharedKernel\SecurityAudit\SafeAuditMetadata;
use Zandu\SharedKernel\SecurityAudit\SecurityAction;
use Zandu\SharedKernel\SecurityAudit\SecurityAuditTrail;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class RoleAssignmentService
{
    public function __construct(
        private OrganizationMembershipRepository $memberships,
        private LastOrganizationOwner $lastOwner,
        private SystemRoleCatalog $systemRoles,
        private TenantTransaction $transaction,
        private Clock $clock,
        private AuthorizationService $authorization,
        private OperationalGuard $operationalGuard,
        private SecurityAuditTrail $audit,
    ) {}

    public function assign(
        OrganizationMembershipId $membershipId,
        RoleId $roleId,
        AccessScope $scope,
        ?DateTimeImmutable $expiresAt,
        ActorContext $actorContext,
    ): OrganizationMembership {
        return $this->transaction->transactional($actorContext->organizationId(), function () use ($membershipId, $roleId, $scope, $expiresAt, $actorContext): OrganizationMembership {
            $this->authorization->authorize($actorContext, PermissionCode::RoleAssign, ResourceScope::organization($actorContext->organizationId()));
            $this->operationalGuard->assertTenant($actorContext);
            $this->systemRoles->getById($roleId);
            $membership = $this->memberships->get($actorContext->organizationId(), $membershipId);
            $this->lastOwner->protectAssignmentChange($membership, $roleId, $actorContext, false);
            $now = $this->clock->now();
            $membership->assignRole(RoleAssignment::assign($roleId, $scope, $actorContext->actorId(), $now, $expiresAt), $actorContext->actorId(), $now);
            $this->memberships->save($membership);
            $action = $this->systemRoles->isOrganizationOwner($roleId) ? SecurityAction::OwnerAssigned : SecurityAction::RoleAssigned;
            $this->audit->recordSuccess($actorContext, $action, ResourceReference::for('organization_membership', $membership->id()), SafeAuditMetadata::fromArray(['roleId' => $roleId->toString()]), $now);

            return $membership;
        });
    }

    public function remove(
        OrganizationMembershipId $membershipId,
        RoleId $roleId,
        ActorContext $actorContext,
    ): OrganizationMembership {
        return $this->transaction->transactional($actorContext->organizationId(), function () use ($membershipId, $roleId, $actorContext): OrganizationMembership {
            $this->authorization->authorize($actorContext, PermissionCode::RoleRevoke, ResourceScope::organization($actorContext->organizationId()));
            $this->operationalGuard->assertTenant($actorContext, OperationalMode::Remediation);
            $this->systemRoles->getById($roleId);
            $membership = $this->memberships->get($actorContext->organizationId(), $membershipId);
            $this->lastOwner->protectAssignmentChange($membership, $roleId, $actorContext, true);
            $now = $this->clock->now();
            $membership->removeRole($roleId, $actorContext->actorId(), $now);
            $this->memberships->save($membership);
            $action = $this->systemRoles->isOrganizationOwner($roleId) ? SecurityAction::OwnerRemoved : SecurityAction::RoleRemoved;
            $this->audit->recordSuccess($actorContext, $action, ResourceReference::for('organization_membership', $membership->id()), SafeAuditMetadata::fromArray(['roleId' => $roleId->toString()]), $now);

            return $membership;
        });
    }
}
