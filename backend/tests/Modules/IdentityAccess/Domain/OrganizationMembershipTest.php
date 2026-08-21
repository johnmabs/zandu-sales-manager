<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\IdentityAccess\Domain;

use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\IdentityAccess\Domain\Access\AccessScope;
use Zandu\Modules\IdentityAccess\Domain\Access\RoleAssignment;
use Zandu\Modules\IdentityAccess\Domain\Access\RoleCode;
use Zandu\Modules\IdentityAccess\Domain\Access\SystemRoleCatalog;
use Zandu\Modules\IdentityAccess\Domain\Membership\MembershipStatus;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembership;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\OrganizationMembershipId;
use Zandu\SharedKernel\Identity\UserId;

final class OrganizationMembershipTest extends TestCase
{
    public function testInvitationCreatesAnActiveVersionedMembership(): void
    {
        $factory = new SymfonyUuidFactory();
        $organizationId = OrganizationId::fromString('0198d1b1-b2a4-7b6e-8e0e-608484906502', $factory);
        $actorId = ActorId::fromString('0198c728-8f2d-7f43-92d8-3f0c75b80186', $factory);
        $membership = OrganizationMembership::activateFromInvitation(
            OrganizationMembershipId::fromString('0198d401-147c-72d5-b75a-a936797ff9c8', $factory),
            $organizationId,
            UserId::fromString('0198d402-8f2d-7f43-92d8-3f0c75b80186', $factory),
            [$this->assignment($factory, $organizationId, $actorId, RoleCode::CASHIER)],
            $actorId,
            new DateTimeImmutable('2026-08-22T11:00:00+01:00'),
        );

        self::assertSame(MembershipStatus::Active, $membership->status());
        self::assertSame(1, $membership->authorizationVersion());
        self::assertSame(1, $membership->version());
        self::assertSame('2026-08-22T10:00:00+00:00', $membership->createdAt()->format('c'));
    }

    public function testSuspendedMembershipCanBeReactivatedByANewInvitation(): void
    {
        $factory = new SymfonyUuidFactory();
        $organizationId = OrganizationId::fromString('0198d1b1-b2a4-7b6e-8e0e-608484906502', $factory);
        $actorId = ActorId::fromString('0198c728-8f2d-7f43-92d8-3f0c75b80186', $factory);
        $membership = OrganizationMembership::reconstitute(
            OrganizationMembershipId::fromString('0198d401-147c-72d5-b75a-a936797ff9c8', $factory),
            $organizationId,
            UserId::fromString('0198d402-8f2d-7f43-92d8-3f0c75b80186', $factory),
            MembershipStatus::Suspended,
            [$this->assignment($factory, $organizationId, $actorId, RoleCode::CASHIER)],
            2,
            ActorId::fromString('0198c728-8f2d-7f43-92d8-3f0c75b80186', $factory),
            new DateTimeImmutable('2026-08-20T10:00:00+00:00'),
            ActorId::fromString('0198c728-8f2d-7f43-92d8-3f0c75b80186', $factory),
            new DateTimeImmutable('2026-08-21T10:00:00+00:00'),
            ActorId::fromString('0198c728-8f2d-7f43-92d8-3f0c75b80186', $factory),
            new DateTimeImmutable('2026-08-21T10:00:00+00:00'),
            null,
            null,
            2,
        );
        $membership->activateFromInvitationAgain(
            [$this->assignment($factory, $organizationId, $actorId, RoleCode::ACCOUNTANT)],
            $actorId,
            new DateTimeImmutable('2026-08-22T10:00:00+00:00'),
        );

        self::assertSame(MembershipStatus::Active, $membership->status());
        self::assertSame(
            (new SystemRoleCatalog($factory))->get(RoleCode::fromString(RoleCode::ACCOUNTANT))->id()->toString(),
            $membership->roleAssignments()[0]->roleId()->toString(),
        );
        self::assertSame(3, $membership->authorizationVersion());
        self::assertSame(3, $membership->version());
    }

    public function testSuspendReactivateAndRevokeIncrementAuthorizationVersion(): void
    {
        $factory = new SymfonyUuidFactory();
        $actor = ActorId::fromString('0198c728-8f2d-7f43-92d8-3f0c75b80186', $factory);
        $organizationId = OrganizationId::fromString('0198d1b1-b2a4-7b6e-8e0e-608484906502', $factory);
        $membership = OrganizationMembership::activateFromInvitation(
            OrganizationMembershipId::fromString('0198d401-147c-72d5-b75a-a936797ff9c8', $factory),
            $organizationId,
            UserId::fromString('0198d402-8f2d-7f43-92d8-3f0c75b80186', $factory),
            [$this->assignment($factory, $organizationId, $actor, RoleCode::CASHIER)],
            $actor,
            new DateTimeImmutable('2026-08-22T10:00:00+00:00'),
        );
        $membership->suspend($actor, new DateTimeImmutable('2026-08-22T11:00:00+00:00'));
        self::assertSame(MembershipStatus::Suspended, $membership->status());
        self::assertSame(2, $membership->authorizationVersion());
        self::assertNotNull($membership->suspendedAt());

        $membership->reactivate($actor, new DateTimeImmutable('2026-08-22T12:00:00+00:00'));
        self::assertSame(MembershipStatus::Active, $membership->status());
        self::assertSame(3, $membership->authorizationVersion());

        $membership->revoke($actor, new DateTimeImmutable('2026-08-22T13:00:00+00:00'));
        self::assertSame(MembershipStatus::Revoked, $membership->status());
        self::assertSame(4, $membership->authorizationVersion());
        self::assertNotNull($membership->revokedAt());
    }

    public function testAssigningAndRemovingRolesChangesAuthorizationVersion(): void
    {
        $factory = new SymfonyUuidFactory();
        $organizationId = OrganizationId::fromString('0198d1b1-b2a4-7b6e-8e0e-608484906502', $factory);
        $actorId = ActorId::fromString('0198c728-8f2d-7f43-92d8-3f0c75b80186', $factory);
        $membership = OrganizationMembership::activateFromInvitation(
            OrganizationMembershipId::fromString('0198d401-147c-72d5-b75a-a936797ff9c8', $factory),
            $organizationId,
            UserId::fromString('0198d402-8f2d-7f43-92d8-3f0c75b80186', $factory),
            [$this->assignment($factory, $organizationId, $actorId, RoleCode::CASHIER)],
            $actorId,
            new DateTimeImmutable('2026-08-22T10:00:00+00:00'),
        );
        $accountant = $this->assignment($factory, $organizationId, $actorId, RoleCode::ACCOUNTANT);

        $membership->assignRole($accountant, $actorId, new DateTimeImmutable('2026-08-22T11:00:00+00:00'));
        self::assertCount(2, $membership->roleAssignments());
        self::assertSame(2, $membership->authorizationVersion());

        $membership->removeRole($accountant->roleId(), $actorId, new DateTimeImmutable('2026-08-22T12:00:00+00:00'));
        self::assertCount(1, $membership->roleAssignments());
        self::assertSame(3, $membership->authorizationVersion());
    }

    public function testActiveMembershipCannotLoseItsOnlyRole(): void
    {
        $factory = new SymfonyUuidFactory();
        $organizationId = OrganizationId::fromString('0198d1b1-b2a4-7b6e-8e0e-608484906502', $factory);
        $actorId = ActorId::fromString('0198c728-8f2d-7f43-92d8-3f0c75b80186', $factory);
        $assignment = $this->assignment($factory, $organizationId, $actorId, RoleCode::CASHIER);
        $membership = OrganizationMembership::activateFromInvitation(
            OrganizationMembershipId::fromString('0198d401-147c-72d5-b75a-a936797ff9c8', $factory),
            $organizationId,
            UserId::fromString('0198d402-8f2d-7f43-92d8-3f0c75b80186', $factory),
            [$assignment],
            $actorId,
            new DateTimeImmutable('2026-08-22T10:00:00+00:00'),
        );

        $this->expectException(LogicException::class);
        $membership->removeRole($assignment->roleId(), $actorId, new DateTimeImmutable('2026-08-22T11:00:00+00:00'));
    }

    private function assignment(SymfonyUuidFactory $factory, OrganizationId $organizationId, ActorId $actorId, string $roleCode): RoleAssignment
    {
        $role = (new SystemRoleCatalog($factory))->get(RoleCode::fromString($roleCode));

        return RoleAssignment::assign(
            $role->id(),
            AccessScope::organization($organizationId),
            $actorId,
            new DateTimeImmutable('2026-08-20T10:00:00+00:00'),
        );
    }
}
