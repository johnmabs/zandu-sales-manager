<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\IdentityAccess\Application;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\IdentityAccess\Application\CurrentSessionQuery;
use Zandu\Modules\IdentityAccess\Domain\Access\AccessScope;
use Zandu\Modules\IdentityAccess\Domain\Access\RoleAssignment;
use Zandu\Modules\IdentityAccess\Domain\Access\ScopedStore;
use Zandu\Modules\IdentityAccess\Domain\Access\SystemRoleCatalog;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembership;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembershipRepository;
use Zandu\Modules\Organization\Application\Contract\OrganizationStoreIds;
use Zandu\Modules\Organization\Application\Contract\OrganizationSummary;
use Zandu\Modules\Organization\Application\Contract\OrganizationSummaryProvider;
use Zandu\Platform\Identity\SymfonyUuidFactory;
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

final class CurrentSessionQueryTest extends TestCase
{
    private const string NOW = '2026-09-09T12:00:00+00:00';

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
        $this->organizationId = OrganizationId::fromString('0199c201-147c-72d5-b75a-a936797ff9c8', $this->factory);
        $this->userId = UserId::fromString('0199c202-147c-72d5-b75a-a936797ff9c8', $this->factory);
        $this->actorId = ActorId::fromString('0199c203-147c-72d5-b75a-a936797ff9c8', $this->factory);
        $this->storeA = StoreId::fromString('0199c204-147c-72d5-b75a-a936797ff9c8', $this->factory);
        $this->storeB = StoreId::fromString('0199c205-147c-72d5-b75a-a936797ff9c8', $this->factory);
        $this->roles = new SystemRoleCatalog($this->factory);
    }

    public function testItProjectsOrganizationWideEffectiveAccessOnTheCurrentActor(): void
    {
        $view = $this->query($this->assignment(0, AccessScope::organization($this->organizationId)))
            ->get($this->actor());

        self::assertSame($this->actorId->toString(), $view->id);
        self::assertSame($this->userId->toString(), $view->userId);
        self::assertSame($this->organizationId->toString(), $view->organizationId);
        self::assertSame(1, $view->authorizationVersion);
        self::assertSame('ORGANIZATION', $view->effectiveAccess->scope['type']);
        self::assertSame([$this->storeA->toString(), $this->storeB->toString()], $view->effectiveAccess->accessibleStoreIds);
        self::assertContains('ORGANIZATION_READ', $view->effectiveAccess->permissions);
        self::assertContains('STORE_READ', $view->effectiveAccess->permissions);
        self::assertSame('Zandu Test', $view->organizations[0]->name);
    }

    public function testItProjectsOnlySelectedStoresWithoutExposingRoleAssignments(): void
    {
        $scope = AccessScope::selectedStores($this->organizationId, [
            new ScopedStore($this->storeB, $this->organizationId),
        ]);

        $view = $this->query($this->assignment(2, $scope))->get($this->actor());

        self::assertSame(
            ['type' => 'SELECTED_STORES', 'storeIds' => [$this->storeB->toString()]],
            $view->effectiveAccess->scope,
        );
        self::assertSame([$this->storeB->toString()], $view->effectiveAccess->accessibleStoreIds);
        self::assertContains('SALE_CREATE', $view->effectiveAccess->permissions);
        self::assertNotContains('ORGANIZATION_UPDATE', $view->effectiveAccess->permissions);
    }

    private function query(RoleAssignment $assignment): CurrentSessionQuery
    {
        $membership = OrganizationMembership::activateFromInvitation(
            OrganizationMembershipId::fromString('0199c206-147c-72d5-b75a-a936797ff9c8', $this->factory),
            $this->organizationId,
            $this->userId,
            [$assignment],
            $this->actorId,
            new DateTimeImmutable('2026-09-09T11:00:00+00:00'),
        );

        return new CurrentSessionQuery(
            new CurrentSessionMembershipRepository($membership),
            $this->roles,
            new FixedOrganizationStoreIds([$this->storeA, $this->storeB]),
            new FixedOrganizationSummaryProvider($this->organizationId),
            new FrozenClock(new DateTimeImmutable(self::NOW)),
        );
    }

    private function assignment(int $roleIndex, AccessScope $scope): RoleAssignment
    {
        return RoleAssignment::assign(
            $this->roles->roles()[$roleIndex]->id(),
            $scope,
            $this->actorId,
            new DateTimeImmutable('2026-09-09T11:00:00+00:00'),
        );
    }

    private function actor(): ActorContext
    {
        return new ActorContext(
            $this->actorId,
            $this->organizationId,
            ActorType::User,
            CorrelationId::fromString('0199c207-147c-72d5-b75a-a936797ff9c8', $this->factory),
            new DateTimeImmutable(self::NOW),
            $this->userId,
            null,
            'session@example.com',
            1,
        );
    }
}

final readonly class CurrentSessionMembershipRepository implements OrganizationMembershipRepository
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

    public function findAll(OrganizationId $organizationId): array
    {
        return [$this->membership];
    }

    public function countActiveWithRoleForUpdate(OrganizationId $organizationId, RoleId $roleId): int
    {
        return 0;
    }
}

final readonly class FixedOrganizationStoreIds implements OrganizationStoreIds
{
    /** @param list<StoreId> $storeIds */
    public function __construct(private array $storeIds) {}

    public function forOrganization(OrganizationId $organizationId): array
    {
        return $this->storeIds;
    }
}

final readonly class FixedOrganizationSummaryProvider implements OrganizationSummaryProvider
{
    public function __construct(private OrganizationId $organizationId) {}

    public function get(OrganizationId $organizationId): OrganizationSummary
    {
        return new OrganizationSummary(
            $this->organizationId->toString(),
            'Zandu Test',
            'ACTIVE',
            'XAF',
            'Africa/Brazzaville',
            'fr_CG',
        );
    }
}
