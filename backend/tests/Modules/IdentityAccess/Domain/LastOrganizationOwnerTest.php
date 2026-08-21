<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\IdentityAccess\Domain;

use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\IdentityAccess\Domain\Access\LastOrganizationOwner;
use Zandu\Modules\IdentityAccess\Domain\Access\RoleCode;
use Zandu\Modules\IdentityAccess\Domain\Invitation\IntendedRoleAssignment;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembership;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembershipRepository;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\OrganizationMembershipId;
use Zandu\SharedKernel\Identity\UserId;

final class LastOrganizationOwnerTest extends TestCase
{
    public function testLastActiveOwnerCannotBeDeactivated(): void
    {
        $owner = $this->membership(RoleCode::ORGANIZATION_OWNER);
        $guard = new LastOrganizationOwner(new OwnerCountMembershipRepository(1));

        $this->expectException(LogicException::class);
        $guard->protectDeactivation($owner);
    }

    public function testOwnerCanBeDeactivatedWhenAnotherActiveOwnerRemains(): void
    {
        $owner = $this->membership(RoleCode::ORGANIZATION_OWNER);
        $guard = new LastOrganizationOwner(new OwnerCountMembershipRepository(2));

        $guard->protectDeactivation($owner);
        self::assertTrue(true);
    }

    public function testNonOwnerDoesNotTriggerOwnerCount(): void
    {
        $repository = new OwnerCountMembershipRepository(0);
        (new LastOrganizationOwner($repository))->protectDeactivation($this->membership(RoleCode::CASHIER));

        self::assertSame(0, $repository->calls);
    }

    private function membership(string $roleCode): OrganizationMembership
    {
        $factory = new SymfonyUuidFactory();
        $actorId = ActorId::fromString('0198d601-147c-72d5-b75a-a936797ff9c9', $factory);

        return OrganizationMembership::activateFromInvitation(
            OrganizationMembershipId::fromString('0198d601-147c-72d5-b75a-a936797ff9c8', $factory),
            OrganizationId::fromString('0198d1b1-b2a4-7b6e-8e0e-608484906502', $factory),
            UserId::fromString('0198d601-147c-72d5-b75a-a936797ff9c7', $factory),
            [IntendedRoleAssignment::forRole($roleCode)],
            $actorId,
            new DateTimeImmutable('2026-08-21T10:00:00+00:00'),
        );
    }
}

final class OwnerCountMembershipRepository implements OrganizationMembershipRepository
{
    public int $calls = 0;

    public function __construct(private readonly int $ownerCount) {}
    public function save(OrganizationMembership $membership): void {}
    public function get(OrganizationId $organizationId, OrganizationMembershipId $membershipId): OrganizationMembership
    {
        throw new LogicException('Not used.');
    }
    public function findByUser(OrganizationId $organizationId, UserId $userId): ?OrganizationMembership
    {
        return null;
    }
    public function countActiveWithRoleForUpdate(OrganizationId $organizationId, RoleCode $roleCode): int
    {
        ++$this->calls;
        return $this->ownerCount;
    }
}
