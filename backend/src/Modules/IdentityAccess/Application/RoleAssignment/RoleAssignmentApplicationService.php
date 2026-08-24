<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application\RoleAssignment;

use DateTimeImmutable;
use InvalidArgumentException;
use Zandu\Modules\IdentityAccess\Application\MembershipView;
use Zandu\Modules\IdentityAccess\Application\MembershipViewFactory;
use Zandu\Modules\IdentityAccess\Domain\Access\AccessScope;
use Zandu\Modules\IdentityAccess\Domain\Access\AccessScopeType;
use Zandu\Modules\IdentityAccess\Domain\Access\ScopedStore;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\OrganizationMembershipId;
use Zandu\SharedKernel\Identity\RoleId;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Identity\UuidFactory;

final readonly class RoleAssignmentApplicationService
{
    public function __construct(
        private RoleAssignmentService $assignments,
        private MembershipViewFactory $views,
        private UuidFactory $uuidFactory,
    ) {}

    /** @param list<string> $storeIds */
    public function assign(
        string $membershipId,
        string $roleId,
        string $scopeType,
        array $storeIds,
        ?string $expiresAt,
        ActorContext $actor,
    ): MembershipView {
        $organizationId = $actor->organizationId();
        $scope = match (AccessScopeType::tryFrom($scopeType)) {
            AccessScopeType::Organization => [] === $storeIds
                ? AccessScope::organization($organizationId)
                : throw new InvalidArgumentException('An organization scope cannot contain store identifiers.'),
            AccessScopeType::SelectedStores => AccessScope::selectedStores(
                $organizationId,
                array_map(fn(string $storeId): ScopedStore => new ScopedStore(
                    StoreId::fromString($storeId, $this->uuidFactory),
                    $organizationId,
                ), $storeIds),
            ),
            default => throw new InvalidArgumentException('A valid role assignment scope type is required.'),
        };
        $membership = $this->assignments->assign(
            OrganizationMembershipId::fromString($membershipId, $this->uuidFactory),
            RoleId::fromString($roleId, $this->uuidFactory),
            $scope,
            null !== $expiresAt ? new DateTimeImmutable($expiresAt) : null,
            $actor,
        );

        return $this->views->fromAggregate($membership);
    }

    public function remove(string $membershipId, string $assignmentId, ActorContext $actor): MembershipView
    {
        $membership = $this->assignments->remove(
            OrganizationMembershipId::fromString($membershipId, $this->uuidFactory),
            RoleId::fromString($assignmentId, $this->uuidFactory),
            $actor,
        );

        return $this->views->fromAggregate($membership);
    }
}
