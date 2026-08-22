<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\IdentityAccess\Application;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\IdentityAccess\Application\EffectiveAuthorizationService;
use Zandu\Modules\IdentityAccess\Domain\Access\AccessScope;
use Zandu\Modules\IdentityAccess\Domain\Access\RoleAssignment;
use Zandu\Modules\IdentityAccess\Domain\Access\ScopedStore;
use Zandu\Modules\IdentityAccess\Domain\Access\SystemRoleCatalog;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembership;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembershipRepository;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Access\AuthorizationDenied;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Context\ActorType;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\OrganizationMembershipId;
use Zandu\SharedKernel\Identity\RoleId;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Identity\UserId;
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\Tests\SharedKernel\Time\FrozenClock;

final class EffectiveAuthorizationServiceTest extends TestCase
{
    private const NOW = '2026-08-23T10:00:00+00:00';

    private SymfonyUuidFactory $factory;
    private OrganizationId $organizationId;
    private UserId $userId;
    private ActorId $actorId;
    private StoreId $storeA;
    private StoreId $storeB;
    private SystemRoleCatalog $roles;

    protected function setUp(): void
    {
        $this->factory = new SymfonyUuidFactory();
        $this->organizationId = OrganizationId::fromString('0198e201-147c-72d5-b75a-a936797ff9c8', $this->factory);
        $this->userId = UserId::fromString('0198e202-147c-72d5-b75a-a936797ff9c8', $this->factory);
        $this->actorId = ActorId::fromString('0198e203-147c-72d5-b75a-a936797ff9c8', $this->factory);
        $this->storeA = StoreId::fromString('0198e204-147c-72d5-b75a-a936797ff9c8', $this->factory);
        $this->storeB = StoreId::fromString('0198e205-147c-72d5-b75a-a936797ff9c8', $this->factory);
        $this->roles = new SystemRoleCatalog($this->factory);
    }

    public function testOrganizationOwnerReceivesOrganizationPermission(): void
    {
        $service = $this->service($this->assignment(0, AccessScope::organization($this->organizationId)));

        $service->authorize($this->context(), PermissionCode::MemberRevoke, ResourceScope::organization($this->organizationId));

        self::addToAssertionCount(1);
    }

    public function testSelectedStoreScopeAllowsOnlyItsStore(): void
    {
        $scope = AccessScope::selectedStores($this->organizationId, [new ScopedStore($this->storeA, $this->organizationId)]);
        $service = $this->service($this->assignment(1, $scope));

        $service->authorize($this->context(), PermissionCode::StoreUpdate, ResourceScope::store($this->organizationId, $this->storeA));
        self::addToAssertionCount(1);

        $this->expectException(AuthorizationDenied::class);
        $service->authorize($this->context(), PermissionCode::StoreUpdate, ResourceScope::store($this->organizationId, $this->storeB));
    }

    public function testExpiredAssignmentIsDenied(): void
    {
        $service = $this->service($this->assignment(
            0,
            AccessScope::organization($this->organizationId),
            new DateTimeImmutable(self::NOW),
        ));

        $this->expectException(AuthorizationDenied::class);
        $service->authorize($this->context(), PermissionCode::OrganizationRead, ResourceScope::organization($this->organizationId));
    }

    public function testStaleAuthorizationVersionIsDenied(): void
    {
        $service = $this->service($this->assignment(0, AccessScope::organization($this->organizationId)));

        $this->expectException(AuthorizationDenied::class);
        $service->authorize($this->context(2), PermissionCode::OrganizationRead, ResourceScope::organization($this->organizationId));
    }

    public function testCrossTenantResourceScopeIsDenied(): void
    {
        $otherOrganization = OrganizationId::fromString('0198e206-147c-72d5-b75a-a936797ff9c8', $this->factory);
        $service = $this->service($this->assignment(0, AccessScope::organization($this->organizationId)));

        $this->expectException(AuthorizationDenied::class);
        $service->authorize($this->context(), PermissionCode::OrganizationRead, ResourceScope::organization($otherOrganization));
    }

    private function service(RoleAssignment $assignment): EffectiveAuthorizationService
    {
        $membership = OrganizationMembership::activateFromInvitation(
            OrganizationMembershipId::fromString('0198e207-147c-72d5-b75a-a936797ff9c8', $this->factory),
            $this->organizationId,
            $this->userId,
            [$assignment],
            $this->actorId,
            new DateTimeImmutable('2026-08-23T09:00:00+00:00'),
        );

        return new EffectiveAuthorizationService(
            new AuthorizationMembershipRepository($membership),
            $this->roles,
            new FrozenClock(new DateTimeImmutable(self::NOW)),
        );
    }

    private function assignment(int $roleIndex, AccessScope $scope, ?DateTimeImmutable $expiresAt = null): RoleAssignment
    {
        return RoleAssignment::assign(
            $this->roles->roles()[$roleIndex]->id(),
            $scope,
            $this->actorId,
            new DateTimeImmutable('2026-08-23T09:00:00+00:00'),
            $expiresAt,
        );
    }

    private function context(int $authorizationVersion = 1): ActorContext
    {
        return new ActorContext(
            $this->actorId,
            $this->organizationId,
            ActorType::User,
            CorrelationId::fromString('0198e208-147c-72d5-b75a-a936797ff9c8', $this->factory),
            new DateTimeImmutable(self::NOW),
            $this->userId,
            null,
            'authorization@example.com',
            $authorizationVersion,
        );
    }
}

final readonly class AuthorizationMembershipRepository implements OrganizationMembershipRepository
{
    public function __construct(private OrganizationMembership $membership) {}
    public function save(OrganizationMembership $membership): void {}
    public function get(OrganizationId $organizationId, OrganizationMembershipId $membershipId): OrganizationMembership
    {
        return $this->membership;
    }
    public function findByUser(OrganizationId $organizationId, UserId $userId): OrganizationMembership
    {
        return $this->membership;
    }
    public function countActiveWithRoleForUpdate(OrganizationId $organizationId, RoleId $roleId): int
    {
        return 0;
    }
}
