<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\IdentityAccess\Domain;

use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\IdentityAccess\Domain\Access\AccessScope;
use Zandu\Modules\IdentityAccess\Domain\Access\LastOrganizationOwner;
use Zandu\Modules\IdentityAccess\Domain\Access\RoleAssignment;
use Zandu\Modules\IdentityAccess\Domain\Access\RoleCode;
use Zandu\Modules\IdentityAccess\Domain\Access\SystemRoleCatalog;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembership;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembershipRepository;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Context\ActorType;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\OrganizationMembershipId;
use Zandu\SharedKernel\Identity\RoleId;
use Zandu\SharedKernel\Identity\UserId;
use Zandu\SharedKernel\Messaging\CorrelationId;

final class LastOrganizationOwnerTest extends TestCase
{
    public function testLastActiveOwnerCannotBeDeactivated(): void
    {
        $owner = $this->membership(RoleCode::ORGANIZATION_OWNER);
        $guard = new LastOrganizationOwner(new OwnerCountMembershipRepository(1), new SystemRoleCatalog(new SymfonyUuidFactory()));

        $this->expectException(LogicException::class);
        $guard->protectDeactivation($owner);
    }

    public function testOwnerCanBeDeactivatedWhenAnotherActiveOwnerRemains(): void
    {
        $owner = $this->membership(RoleCode::ORGANIZATION_OWNER);
        $guard = new LastOrganizationOwner(new OwnerCountMembershipRepository(2), new SystemRoleCatalog(new SymfonyUuidFactory()));

        $guard->protectDeactivation($owner);
        self::assertTrue(true);
    }

    public function testNonOwnerDoesNotTriggerOwnerCount(): void
    {
        $repository = new OwnerCountMembershipRepository(0);
        (new LastOrganizationOwner($repository, new SystemRoleCatalog(new SymfonyUuidFactory())))->protectDeactivation($this->membership(RoleCode::CASHIER));

        self::assertSame(0, $repository->calls);
    }

    public function testOnlyAnActiveOwnerCanChangeOwnerAssignments(): void
    {
        $factory = new SymfonyUuidFactory();
        $cashier = $this->membership(RoleCode::CASHIER);
        $repository = new OwnerCountMembershipRepository(2, $cashier);
        $guard = new LastOrganizationOwner($repository, new SystemRoleCatalog($factory));

        $this->expectException(LogicException::class);
        $guard->protectAssignmentChange(
            $cashier,
            (new SystemRoleCatalog($factory))->organizationOwnerRoleId(),
            $this->context($factory, $cashier),
            false,
        );
    }

    public function testActiveOwnerCanRemoveOwnerWhenAnotherOneRemains(): void
    {
        $factory = new SymfonyUuidFactory();
        $owner = $this->membership(RoleCode::ORGANIZATION_OWNER);
        $guard = new LastOrganizationOwner(new OwnerCountMembershipRepository(2, $owner), new SystemRoleCatalog($factory));

        $guard->protectAssignmentChange(
            $owner,
            (new SystemRoleCatalog($factory))->organizationOwnerRoleId(),
            $this->context($factory, $owner),
            true,
        );
        self::assertTrue(true);
    }

    private function membership(string $roleCode): OrganizationMembership
    {
        $factory = new SymfonyUuidFactory();
        $actorId = ActorId::fromString('0198d601-147c-72d5-b75a-a936797ff9c9', $factory);
        $organizationId = OrganizationId::fromString('0198d1b1-b2a4-7b6e-8e0e-608484906502', $factory);
        $role = (new SystemRoleCatalog($factory))->get(RoleCode::fromString($roleCode));

        return OrganizationMembership::activateFromInvitation(
            OrganizationMembershipId::fromString('0198d601-147c-72d5-b75a-a936797ff9c8', $factory),
            $organizationId,
            UserId::fromString('0198d601-147c-72d5-b75a-a936797ff9c7', $factory),
            [RoleAssignment::assign($role->id(), AccessScope::organization($organizationId), $actorId, new DateTimeImmutable('2026-08-21T10:00:00+00:00'))],
            $actorId,
            new DateTimeImmutable('2026-08-21T10:00:00+00:00'),
        );
    }

    private function context(SymfonyUuidFactory $factory, OrganizationMembership $membership): ActorContext
    {
        return new ActorContext(
            $membership->updatedBy(),
            $membership->organizationId(),
            ActorType::User,
            CorrelationId::fromString('0198d601-147c-72d5-b75a-a936797ff9c6', $factory),
            new DateTimeImmutable('2026-08-21T10:00:00+00:00'),
            $membership->userId(),
        );
    }
}

final class OwnerCountMembershipRepository implements OrganizationMembershipRepository
{
    public int $calls = 0;

    public function __construct(private readonly int $ownerCount, private readonly ?OrganizationMembership $membership = null) {}
    public function save(OrganizationMembership $membership): void {}
    public function get(OrganizationId $organizationId, OrganizationMembershipId $membershipId): OrganizationMembership
    {
        throw new LogicException('Not used.');
    }
    public function findByUser(OrganizationId $organizationId, UserId $userId): ?OrganizationMembership
    {
        return $this->membership;
    }
    public function countActiveWithRoleForUpdate(OrganizationId $organizationId, RoleId $roleId): int
    {
        ++$this->calls;
        return $this->ownerCount;
    }
}
