<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\IdentityAccess\Domain;

use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\IdentityAccess\Domain\Invitation\Event\OrganizationInvitationAccepted;
use Zandu\Modules\IdentityAccess\Domain\Invitation\Event\OrganizationMemberInvited;
use Zandu\Modules\IdentityAccess\Domain\Invitation\IntendedRoleAssignment;
use Zandu\Modules\IdentityAccess\Domain\Invitation\InvitationEmail;
use Zandu\Modules\IdentityAccess\Domain\Invitation\InvitationStatus;
use Zandu\Modules\IdentityAccess\Domain\Invitation\OrganizationInvitation;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\OrganizationInvitationId;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Identity\UserId;

final class OrganizationInvitationTest extends TestCase
{
    private const INVITATION_ID = '0198d301-147c-72d5-b75a-a936797ff9c8';
    private const ORGANIZATION_ID = '0198d1b1-b2a4-7b6e-8e0e-608484906502';
    private const STORE_ID = '0198d233-147c-72d5-b75a-a936797ff9c8';
    private const ACTOR_ID = '0198c728-8f2d-7f43-92d8-3f0c75b80186';
    private const USER_ID = '0198d302-8f2d-7f43-92d8-3f0c75b80186';

    public function testInvitationNormalizesEmailAndCapturesRoleIntent(): void
    {
        $invitation = $this->invitation();

        self::assertSame('member@example.com', $invitation->email()->value());
        self::assertSame('STORE_MANAGER', $invitation->intendedRoleAssignments()[0]->roleCode());
        self::assertSame(self::STORE_ID, $invitation->intendedRoleAssignments()[0]->storeIds()[0]->toString());
        self::assertSame(InvitationStatus::Pending, $invitation->status());
        self::assertInstanceOf(OrganizationMemberInvited::class, $invitation->releaseEvents()[0]);
    }

    public function testAcceptanceIsSingleUse(): void
    {
        $invitation = $this->invitation();
        $invitation->accept($this->userId(), $this->actorId(), new DateTimeImmutable('2026-08-22T11:00:00+00:00'));

        self::assertSame(InvitationStatus::Accepted, $invitation->status());
        self::assertSame(self::USER_ID, $invitation->acceptedBy()?->toString());
        self::assertInstanceOf(OrganizationInvitationAccepted::class, $invitation->releaseEvents()[1]);

        $this->expectException(LogicException::class);
        $invitation->accept($this->userId(), $this->actorId(), new DateTimeImmutable('2026-08-22T11:01:00+00:00'));
    }

    public function testDueInvitationExpiresAndCannotBeAccepted(): void
    {
        $invitation = $this->invitation();

        $this->expectException(LogicException::class);
        try {
            $invitation->accept($this->userId(), $this->actorId(), new DateTimeImmutable('2026-08-23T10:00:00+00:00'));
        } finally {
            self::assertSame(InvitationStatus::Expired, $invitation->status());
        }
    }

    public function testPendingInvitationCanBeCancelledOnlyOnce(): void
    {
        $invitation = $this->invitation();
        $invitation->cancel(new DateTimeImmutable('2026-08-22T11:00:00+00:00'));
        self::assertSame(InvitationStatus::Cancelled, $invitation->status());

        $this->expectException(LogicException::class);
        $invitation->cancel(new DateTimeImmutable('2026-08-22T11:01:00+00:00'));
    }

    private function invitation(): OrganizationInvitation
    {
        return OrganizationInvitation::invite(
            OrganizationInvitationId::fromString(self::INVITATION_ID, new SymfonyUuidFactory()),
            OrganizationId::fromString(self::ORGANIZATION_ID, new SymfonyUuidFactory()),
            InvitationEmail::fromString(' Member@Example.COM '),
            $this->actorId(),
            'persisted-token-hash',
            new DateTimeImmutable('2026-08-23T10:00:00+00:00'),
            [IntendedRoleAssignment::forRole('store_manager', [StoreId::fromString(self::STORE_ID, new SymfonyUuidFactory())])],
            new DateTimeImmutable('2026-08-22T10:00:00+00:00'),
        );
    }

    private function actorId(): ActorId
    {
        return ActorId::fromString(self::ACTOR_ID, new SymfonyUuidFactory());
    }
    private function userId(): UserId
    {
        return UserId::fromString(self::USER_ID, new SymfonyUuidFactory());
    }
}
