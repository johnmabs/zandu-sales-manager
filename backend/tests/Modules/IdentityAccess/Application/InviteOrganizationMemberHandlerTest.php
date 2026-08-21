<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\IdentityAccess\Application;

use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\IdentityAccess\Application\CancelOrganizationInvitation\CancelOrganizationInvitation;
use Zandu\Modules\IdentityAccess\Application\CancelOrganizationInvitation\CancelOrganizationInvitationHandler;
use Zandu\Modules\IdentityAccess\Application\Contract\InvitationTokenService;
use Zandu\Modules\IdentityAccess\Application\ExpireOrganizationInvitations\ExpireOrganizationInvitations;
use Zandu\Modules\IdentityAccess\Application\ExpireOrganizationInvitations\ExpireOrganizationInvitationsHandler;
use Zandu\Modules\IdentityAccess\Application\InviteOrganizationMember\InviteOrganizationMember;
use Zandu\Modules\IdentityAccess\Application\InviteOrganizationMember\InviteOrganizationMemberHandler;
use Zandu\Modules\IdentityAccess\Domain\Access\LastOrganizationOwner;
use Zandu\Modules\IdentityAccess\Domain\Access\RoleCode;
use Zandu\Modules\IdentityAccess\Domain\Access\SystemRoleCatalog;
use Zandu\Modules\IdentityAccess\Domain\Invitation\ActiveInvitationAlreadyExists;
use Zandu\Modules\IdentityAccess\Domain\Invitation\IntendedRoleAssignment;
use Zandu\Modules\IdentityAccess\Domain\Invitation\InvitationEmail;
use Zandu\Modules\IdentityAccess\Domain\Invitation\InvitationStatus;
use Zandu\Modules\IdentityAccess\Domain\Invitation\OrganizationInvitation;
use Zandu\Modules\IdentityAccess\Domain\Invitation\OrganizationInvitationNotFound;
use Zandu\Modules\IdentityAccess\Domain\Invitation\OrganizationInvitationRepository;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembership;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembershipRepository;
use Zandu\Modules\IdentityAccess\Infrastructure\Security\SecureInvitationTokenService;
use Zandu\Modules\Organization\Application\Contract\MemberInvitationPolicy;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Context\ActorType;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\OrganizationInvitationId;
use Zandu\SharedKernel\Identity\OrganizationMembershipId;
use Zandu\SharedKernel\Identity\RoleId;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Identity\UserId;
use Zandu\SharedKernel\Identity\Uuid;
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\Tests\SharedKernel\Time\FrozenClock;

final class InviteOrganizationMemberHandlerTest extends TestCase
{
    private const ORGANIZATION_ID = '0198d1b1-b2a4-7b6e-8e0e-608484906502';
    private const INVITATION_ID = '0198d301-147c-72d5-b75a-a936797ff9c8';
    private const ACTOR_ID = '0198c728-8f2d-7f43-92d8-3f0c75b80186';
    private const CORRELATION_ID = '0198c729-19da-75be-b508-1a4b36cf8d7a';

    public function testItValidatesPolicyAndReturnsRawTokenOnlyAtCreation(): void
    {
        $repository = new InvitationTestRepository();
        $policy = new RecordingInvitationPolicy();
        $tokens = new SecureInvitationTokenService('test-pepper', new SymfonyUuidFactory());
        $transaction = new InvitationTestTransaction();
        $handler = $this->handler($repository, $policy, $tokens, $transaction);

        $created = $handler(new InviteOrganizationMember(
            'Member@Example.com',
            [IntendedRoleAssignment::forRole('CASHIER')],
            null,
            $this->context(),
        ));

        self::assertSame('member@example.com', $created->invitation->email()->value());
        self::assertNotSame($created->revealToken(), $created->invitation->tokenHash());
        self::assertSame($created->invitation->tokenHash(), $tokens->hash($created->revealToken()));
        self::assertSame(1, $policy->calls);
        self::assertSame(self::ORGANIZATION_ID, $transaction->organizationId?->toString());
        self::assertSame('2026-08-29T10:00:00+00:00', $created->invitation->expiresAt()->format('c'));
    }

    public function testItRejectsConflictingPendingInvitation(): void
    {
        $repository = new InvitationTestRepository();
        $handler = $this->handler($repository, new RecordingInvitationPolicy(), new SecureInvitationTokenService('test-pepper', new SymfonyUuidFactory()), new InvitationTestTransaction());
        $command = new InviteOrganizationMember('member@example.com', [IntendedRoleAssignment::forRole('CASHIER')], null, $this->context());
        $handler($command);

        $this->expectException(ActiveInvitationAlreadyExists::class);
        $handler($command);
    }

    public function testOwnerInvitationRequiresAnActiveOwner(): void
    {
        $handler = $this->handler(
            new InvitationTestRepository(),
            new RecordingInvitationPolicy(),
            new SecureInvitationTokenService('test-pepper', new SymfonyUuidFactory()),
            new InvitationTestTransaction(),
        );

        $this->expectException(LogicException::class);
        $handler(new InviteOrganizationMember(
            'owner@example.com',
            [IntendedRoleAssignment::forRole(RoleCode::ORGANIZATION_OWNER)],
            null,
            $this->context(),
        ));
    }

    public function testCancellationAndMaterializedExpirationPersistTransitions(): void
    {
        $repository = new InvitationTestRepository();
        $policy = new RecordingInvitationPolicy();
        $transaction = new InvitationTestTransaction();
        $tokens = new SecureInvitationTokenService('test-pepper', new SymfonyUuidFactory());
        $created = $this->handler($repository, $policy, $tokens, $transaction)(new InviteOrganizationMember(
            'first@example.com',
            [IntendedRoleAssignment::forRole('CASHIER')],
            null,
            $this->context(),
        ));
        $cancel = new CancelOrganizationInvitationHandler(
            $repository,
            $policy,
            new FrozenClock(new DateTimeImmutable('2026-08-22T11:00:00+00:00')),
            $transaction,
            new AllowAllAuthorizationService(),
            new AllowAllOperationalGuard(),
        );
        self::assertSame(InvitationStatus::Cancelled, $cancel(new CancelOrganizationInvitation($created->invitation->id(), $this->context()))->status());

        $expired = OrganizationInvitation::invite(
            OrganizationInvitationId::fromString('0198d311-147c-72d5-b75a-a936797ff9c8', new SymfonyUuidFactory()),
            OrganizationId::fromString(self::ORGANIZATION_ID, new SymfonyUuidFactory()),
            InvitationEmail::fromString('expired@example.com'),
            ActorId::fromString(self::ACTOR_ID, new SymfonyUuidFactory()),
            'expired-token-hash',
            new DateTimeImmutable('2026-08-22T12:00:00+00:00'),
            [IntendedRoleAssignment::forRole('CASHIER')],
            new DateTimeImmutable('2026-08-22T10:00:00+00:00'),
        );
        $repository->save($expired);
        $expire = new ExpireOrganizationInvitationsHandler(
            $repository,
            new FrozenClock(new DateTimeImmutable('2026-08-22T13:00:00+00:00')),
            $transaction,
        );
        self::assertSame(1, $expire(new ExpireOrganizationInvitations($this->context())));
        self::assertSame(InvitationStatus::Expired, $expired->status());
    }

    private function handler(OrganizationInvitationRepository $repository, MemberInvitationPolicy $policy, InvitationTokenService $tokens, TenantTransaction $transaction): InviteOrganizationMemberHandler
    {
        $uuid = (new SymfonyUuidFactory())->fromString(self::INVITATION_ID);
        $systemRoles = new SystemRoleCatalog(new SymfonyUuidFactory());
        return new InviteOrganizationMemberHandler(
            $repository,
            $policy,
            $tokens,
            new class ($uuid) implements IdGenerator {
                public function __construct(private readonly Uuid $uuid) {}
                public function generate(): Uuid
                {
                    return $this->uuid;
                }
            },
            new FrozenClock(new DateTimeImmutable('2026-08-22T10:00:00+00:00')),
            $transaction,
            $systemRoles,
            new LastOrganizationOwner(new EmptyMembershipRepository(), $systemRoles),
            new AllowAllAuthorizationService(),
            new AllowAllOperationalGuard(),
        );
    }

    private function context(): ActorContext
    {
        $factory = new SymfonyUuidFactory();
        return new ActorContext(
            ActorId::fromString(self::ACTOR_ID, $factory),
            OrganizationId::fromString(self::ORGANIZATION_ID, $factory),
            ActorType::User,
            CorrelationId::fromString(self::CORRELATION_ID, $factory),
            new DateTimeImmutable('2026-08-22T09:00:00+00:00'),
        );
    }
}

final class RecordingInvitationPolicy implements MemberInvitationPolicy
{
    public int $calls = 0;
    public function assertCanInvite(ActorContext $actorContext, array $selectedStoreIds): void
    {
        ++$this->calls;
    }
}

final class EmptyMembershipRepository implements OrganizationMembershipRepository
{
    public function save(OrganizationMembership $membership): void {}
    public function get(OrganizationId $organizationId, OrganizationMembershipId $membershipId): OrganizationMembership
    {
        throw new LogicException('Membership not found.');
    }
    public function findByUser(OrganizationId $organizationId, UserId $userId): ?OrganizationMembership
    {
        return null;
    }
    public function countActiveWithRoleForUpdate(OrganizationId $organizationId, RoleId $roleId): int
    {
        return 0;
    }
}

final class InvitationTestTransaction implements TenantTransaction
{
    public ?OrganizationId $organizationId = null;
    public function transactional(OrganizationId $organizationId, callable $operation): mixed
    {
        $this->organizationId = $organizationId;
        return $operation();
    }
}

final class InvitationTestRepository implements OrganizationInvitationRepository
{
    /** @var array<string, OrganizationInvitation> */ private array $invitations = [];
    public function save(OrganizationInvitation $invitation): void
    {
        $this->invitations[$invitation->id()->toString()] = $invitation;
    }
    public function get(OrganizationId $organizationId, OrganizationInvitationId $invitationId): OrganizationInvitation
    {
        $invitation = $this->invitations[$invitationId->toString()] ?? null;
        return $invitation instanceof OrganizationInvitation && $invitation->organizationId()->equals($organizationId)
            ? $invitation
            : throw OrganizationInvitationNotFound::forToken();
    }
    public function getByTokenHash(OrganizationId $organizationId, string $tokenHash): OrganizationInvitation
    {
        throw OrganizationInvitationNotFound::forToken();
    }
    public function pendingExists(OrganizationId $organizationId, InvitationEmail $email, DateTimeImmutable $now): bool
    {
        foreach ($this->invitations as $invitation) {
            if ($invitation->organizationId()->equals($organizationId) && $invitation->email()->equals($email) && $invitation->expiresAt() > $now) {
                return true;
            }
        }
        return false;
    }
    public function findExpiredPending(OrganizationId $organizationId, DateTimeImmutable $now, int $limit): array
    {
        return array_slice(array_values(array_filter(
            $this->invitations,
            static fn(OrganizationInvitation $invitation): bool => $invitation->organizationId()->equals($organizationId)
                && InvitationStatus::Pending === $invitation->status() && $invitation->expiresAt() <= $now,
        )), 0, $limit);
    }
}
