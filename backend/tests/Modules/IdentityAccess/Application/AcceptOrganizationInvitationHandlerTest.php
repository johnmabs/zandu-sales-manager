<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\IdentityAccess\Application;

use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\IdentityAccess\Application\AcceptOrganizationInvitation\AcceptOrganizationInvitation;
use Zandu\Modules\IdentityAccess\Application\AcceptOrganizationInvitation\AcceptOrganizationInvitationHandler;
use Zandu\Modules\IdentityAccess\Domain\Access\SystemRoleCatalog;
use Zandu\Modules\IdentityAccess\Domain\Invitation\IntendedRoleAssignment;
use Zandu\Modules\IdentityAccess\Domain\Invitation\InvitationEmail;
use Zandu\Modules\IdentityAccess\Domain\Invitation\InvitationStatus;
use Zandu\Modules\IdentityAccess\Domain\Invitation\OrganizationInvitation;
use Zandu\Modules\IdentityAccess\Domain\Invitation\OrganizationInvitationNotFound;
use Zandu\Modules\IdentityAccess\Domain\Invitation\OrganizationInvitationRepository;
use Zandu\Modules\IdentityAccess\Domain\Membership\MembershipStatus;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembership;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembershipRepository;
use Zandu\Modules\IdentityAccess\Infrastructure\Security\SecureInvitationTokenService;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Context\ActorType;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\OrganizationInvitationId;
use Zandu\SharedKernel\Identity\OrganizationMembershipId;
use Zandu\SharedKernel\Identity\RoleId;
use Zandu\SharedKernel\Identity\UserId;
use Zandu\SharedKernel\Identity\Uuid;
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\Tests\SharedKernel\Time\FrozenClock;

final class AcceptOrganizationInvitationHandlerTest extends TestCase
{
    private const ORGANIZATION_ID = '0198d1b1-b2a4-7b6e-8e0e-608484906502';
    private const USER_ID = '0198d402-8f2d-7f43-92d8-3f0c75b80186';
    private const ACTOR_ID = '0198c728-8f2d-7f43-92d8-3f0c75b80186';

    public function testItAtomicallyAcceptsInvitationAndCreatesMembership(): void
    {
        [$handler, $rawToken, $invitations, $memberships, $transaction] = $this->fixture();
        $membership = $handler(new AcceptOrganizationInvitation($rawToken, $this->context('member@example.com')));

        self::assertSame(MembershipStatus::Active, $membership->status());
        self::assertSame(
            (new SystemRoleCatalog(new SymfonyUuidFactory()))->get(\Zandu\Modules\IdentityAccess\Domain\Access\RoleCode::fromString('CASHIER'))->id()->toString(),
            $membership->roleAssignments()[0]->roleId()->toString(),
        );
        self::assertSame(InvitationStatus::Accepted, $invitations->invitation->status());
        self::assertSame($membership, $memberships->membership);
        self::assertSame(self::ORGANIZATION_ID, $transaction->organizationId?->toString());
    }

    public function testItRejectsAuthenticatedEmailMismatch(): void
    {
        [$handler, $rawToken] = $this->fixture();
        $this->expectException(LogicException::class);
        $handler(new AcceptOrganizationInvitation($rawToken, $this->context('attacker@example.com')));
    }

    public function testAcceptedTokenCannotBeUsedTwice(): void
    {
        [$handler, $rawToken] = $this->fixture();
        $command = new AcceptOrganizationInvitation($rawToken, $this->context('member@example.com'));
        $handler($command);
        $this->expectException(LogicException::class);
        $handler($command);
    }

    /** @return array{AcceptOrganizationInvitationHandler,string,AcceptanceInvitationRepository,AcceptanceMembershipRepository,AcceptanceTransaction} */
    private function fixture(): array
    {
        $factory = new SymfonyUuidFactory();
        $organizationId = OrganizationId::fromString(self::ORGANIZATION_ID, $factory);
        $tokens = new SecureInvitationTokenService('test-pepper', $factory);
        $issued = $tokens->issue($organizationId);
        $invitation = OrganizationInvitation::invite(
            OrganizationInvitationId::fromString('0198d301-147c-72d5-b75a-a936797ff9c8', $factory),
            $organizationId,
            InvitationEmail::fromString('member@example.com'),
            ActorId::fromString(self::ACTOR_ID, $factory),
            $issued->tokenHash(),
            new DateTimeImmutable('2026-08-23T10:00:00+00:00'),
            [IntendedRoleAssignment::forRole('CASHIER')],
            new DateTimeImmutable('2026-08-22T10:00:00+00:00'),
        );
        $invitations = new AcceptanceInvitationRepository($invitation);
        $memberships = new AcceptanceMembershipRepository();
        $transaction = new AcceptanceTransaction();
        $uuid = $factory->fromString('0198d401-147c-72d5-b75a-a936797ff9c8');
        $handler = new AcceptOrganizationInvitationHandler(
            $invitations,
            $memberships,
            $tokens,
            new class ($uuid) implements IdGenerator {
                public function __construct(private readonly Uuid $uuid) {}
                public function generate(): Uuid
                {
                    return $this->uuid;
                }
            },
            new FrozenClock(new DateTimeImmutable('2026-08-22T11:00:00+00:00')),
            $transaction,
            new SystemRoleCatalog($factory),
        );
        return [$handler, $issued->reveal(), $invitations, $memberships, $transaction];
    }

    private function context(string $email): ActorContext
    {
        $factory = new SymfonyUuidFactory();
        return new ActorContext(
            ActorId::fromString(self::ACTOR_ID, $factory),
            OrganizationId::fromString(self::ORGANIZATION_ID, $factory),
            ActorType::User,
            CorrelationId::fromString('0198c729-19da-75be-b508-1a4b36cf8d7a', $factory),
            new DateTimeImmutable('2026-08-22T09:00:00+00:00'),
            UserId::fromString(self::USER_ID, $factory),
            null,
            $email,
        );
    }
}

final class AcceptanceInvitationRepository implements OrganizationInvitationRepository
{
    public function __construct(public OrganizationInvitation $invitation) {}
    public function save(OrganizationInvitation $invitation): void
    {
        $this->invitation = $invitation;
    }
    public function get(OrganizationId $organizationId, OrganizationInvitationId $invitationId): OrganizationInvitation
    {
        return $this->invitation;
    }
    public function getByTokenHash(OrganizationId $organizationId, string $tokenHash): OrganizationInvitation
    {
        return $this->invitation->organizationId()->equals($organizationId) && hash_equals($this->invitation->tokenHash(), $tokenHash)
            ? $this->invitation : throw OrganizationInvitationNotFound::forToken();
    }
    public function pendingExists(OrganizationId $organizationId, InvitationEmail $email, DateTimeImmutable $now): bool
    {
        return false;
    }
    public function findExpiredPending(OrganizationId $organizationId, DateTimeImmutable $now, int $limit): array
    {
        return [];
    }
}

final class AcceptanceMembershipRepository implements OrganizationMembershipRepository
{
    public ?OrganizationMembership $membership = null;
    public function save(OrganizationMembership $membership): void
    {
        $this->membership = $membership;
    }
    public function get(OrganizationId $organizationId, OrganizationMembershipId $membershipId): OrganizationMembership
    {
        return $this->membership ?? throw new LogicException('Membership not found.');
    }
    public function findByUser(OrganizationId $organizationId, UserId $userId): ?OrganizationMembership
    {
        return $this->membership;
    }
    public function countActiveWithRoleForUpdate(OrganizationId $organizationId, RoleId $roleId): int
    {
        return 0;
    }
}

final class AcceptanceTransaction implements TenantTransaction
{
    public ?OrganizationId $organizationId = null;
    public function transactional(OrganizationId $organizationId, callable $operation): mixed
    {
        $this->organizationId = $organizationId;
        return $operation();
    }
}
