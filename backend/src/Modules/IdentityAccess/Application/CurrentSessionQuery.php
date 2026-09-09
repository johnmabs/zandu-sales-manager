<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application;

use LogicException;
use Zandu\Modules\IdentityAccess\Domain\Access\AccessScopeType;
use Zandu\Modules\IdentityAccess\Domain\Access\SystemRoleCatalog;
use Zandu\Modules\IdentityAccess\Domain\Membership\MembershipStatus;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembershipRepository;
use Zandu\Modules\Organization\Application\Contract\OrganizationStoreIds;
use Zandu\Modules\Organization\Application\Contract\OrganizationSummaryProvider;
use Zandu\SharedKernel\Access\AuthorizationDenied;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Time\Clock;

final readonly class CurrentSessionQuery
{
    public function __construct(
        private OrganizationMembershipRepository $memberships,
        private SystemRoleCatalog $roles,
        private OrganizationStoreIds $storeIds,
        private OrganizationSummaryProvider $organizations,
        private Clock $clock,
    ) {}

    public function get(ActorContext $actor): CurrentSessionView
    {
        $userId = $actor->userId();
        $membership = null === $userId ? null : $this->memberships->findByUser($actor->organizationId(), $userId);
        if (null === $userId || null === $membership
            || MembershipStatus::Active !== $membership->status()
            || $membership->authorizationVersion() !== $actor->authorizationVersion()) {
            throw AuthorizationDenied::forPermission($actor, PermissionCode::StoreRead, ResourceScope::organization($actor->organizationId()));
        }

        $permissions = [];
        $selectedStoreIds = [];
        $organizationScoped = false;
        $now = $this->clock->now();
        foreach ($membership->roleAssignments() as $assignment) {
            if ($assignment->isExpiredAt($now)) {
                continue;
            }
            try {
                $role = $this->roles->getById($assignment->roleId());
            } catch (LogicException) {
                continue;
            }
            if (AccessScopeType::Organization === $assignment->scope()->type()) {
                $organizationScoped = true;
            } else {
                foreach ($assignment->scope()->storeIds() as $storeId) {
                    $selectedStoreIds[$storeId->toString()] = $storeId->toString();
                }
            }
            foreach (PermissionCode::cases() as $permission) {
                if ($assignment->grants($role, $permission, $now)) {
                    $permissions[$permission->value] = $permission->value;
                }
            }
        }

        ksort($permissions);
        if ($organizationScoped) {
            $accessibleStoreIds = array_map(
                static fn(StoreId $id): string => $id->toString(),
                $this->storeIds->forOrganization($actor->organizationId()),
            );
            sort($accessibleStoreIds);
            $scope = ['type' => AccessScopeType::Organization->value];
        } else {
            ksort($selectedStoreIds);
            $accessibleStoreIds = array_values($selectedStoreIds);
            $scope = [
                'type' => AccessScopeType::SelectedStores->value,
                'storeIds' => $accessibleStoreIds,
            ];
        }

        $organization = $this->organizations->get($actor->organizationId());

        return new CurrentSessionView(
            $actor->actorId()->toString(),
            $userId->toString(),
            $actor->organizationId()->toString(),
            $membership->authorizationVersion(),
            new EffectiveAccessView(
                $actor->organizationId()->toString(),
                $membership->authorizationVersion(),
                array_values($permissions),
                $scope,
                $accessibleStoreIds,
            ),
            $actor->email(),
            [new CurrentOrganizationView(
                $organization->id,
                $organization->name,
                $organization->status,
                $organization->defaultCurrency,
                $organization->defaultTimeZone,
                $organization->defaultLocale,
            )],
        );
    }
}
