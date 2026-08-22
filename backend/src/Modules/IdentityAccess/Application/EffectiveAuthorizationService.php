<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application;

use LogicException;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\IdentityAccess\Domain\Access\SystemRoleCatalog;
use Zandu\Modules\IdentityAccess\Domain\Membership\MembershipStatus;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembershipRepository;
use Zandu\SharedKernel\Access\AuthorizationDenied;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Time\Clock;

final readonly class EffectiveAuthorizationService implements AuthorizationService
{
    public function __construct(
        private OrganizationMembershipRepository $memberships,
        private SystemRoleCatalog $systemRoles,
        private Clock $clock,
    ) {}

    public function authorize(ActorContext $actorContext, PermissionCode $permission, ResourceScope $resourceScope): void
    {
        $userId = $actorContext->userId();
        $claimedVersion = $actorContext->authorizationVersion();
        if (!$resourceScope->organizationId->equals($actorContext->organizationId())
            || null === $userId || null === $claimedVersion) {
            throw AuthorizationDenied::forPermission($actorContext, $permission, $resourceScope);
        }

        $membership = $this->memberships->findByUser($actorContext->organizationId(), $userId);
        if (null === $membership || MembershipStatus::Active !== $membership->status()
            || $membership->authorizationVersion() !== $claimedVersion) {
            throw AuthorizationDenied::forPermission($actorContext, $permission, $resourceScope);
        }

        $now = $this->clock->now();
        foreach ($membership->roleAssignments() as $assignment) {
            try {
                $role = $this->systemRoles->getById($assignment->roleId());
            } catch (LogicException) {
                continue;
            }

            if ($assignment->grants($role, $permission, $now, $resourceScope->storeId)) {
                return;
            }
        }

        throw AuthorizationDenied::forPermission($actorContext, $permission, $resourceScope);
    }
}
