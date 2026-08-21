<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\IdentityAccess\Domain;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\IdentityAccess\Domain\Invitation\IntendedRoleAssignment;
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
        $membership = OrganizationMembership::activateFromInvitation(
            OrganizationMembershipId::fromString('0198d401-147c-72d5-b75a-a936797ff9c8', $factory),
            OrganizationId::fromString('0198d1b1-b2a4-7b6e-8e0e-608484906502', $factory),
            UserId::fromString('0198d402-8f2d-7f43-92d8-3f0c75b80186', $factory),
            [IntendedRoleAssignment::forRole('CASHIER')],
            ActorId::fromString('0198c728-8f2d-7f43-92d8-3f0c75b80186', $factory),
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
        $membership = OrganizationMembership::reconstitute(
            OrganizationMembershipId::fromString('0198d401-147c-72d5-b75a-a936797ff9c8', $factory),
            OrganizationId::fromString('0198d1b1-b2a4-7b6e-8e0e-608484906502', $factory),
            UserId::fromString('0198d402-8f2d-7f43-92d8-3f0c75b80186', $factory),
            MembershipStatus::Suspended,
            [IntendedRoleAssignment::forRole('CASHIER')],
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
            [IntendedRoleAssignment::forRole('ACCOUNTANT')],
            ActorId::fromString('0198c728-8f2d-7f43-92d8-3f0c75b80186', $factory),
            new DateTimeImmutable('2026-08-22T10:00:00+00:00'),
        );

        self::assertSame(MembershipStatus::Active, $membership->status());
        self::assertSame('ACCOUNTANT', $membership->roleAssignments()[0]->roleCode());
        self::assertSame(3, $membership->authorizationVersion());
        self::assertSame(3, $membership->version());
    }

    public function testSuspendReactivateAndRevokeIncrementAuthorizationVersion(): void
    {
        $factory = new SymfonyUuidFactory();
        $actor = ActorId::fromString('0198c728-8f2d-7f43-92d8-3f0c75b80186', $factory);
        $membership = OrganizationMembership::activateFromInvitation(
            OrganizationMembershipId::fromString('0198d401-147c-72d5-b75a-a936797ff9c8', $factory),
            OrganizationId::fromString('0198d1b1-b2a4-7b6e-8e0e-608484906502', $factory),
            UserId::fromString('0198d402-8f2d-7f43-92d8-3f0c75b80186', $factory),
            [IntendedRoleAssignment::forRole('CASHIER')],
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
}
