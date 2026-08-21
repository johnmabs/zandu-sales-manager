<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\IdentityAccess;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;
use Zandu\Modules\IdentityAccess\Application\AcceptOrganizationInvitation\AcceptOrganizationInvitation;
use Zandu\Modules\IdentityAccess\Application\AcceptOrganizationInvitation\AcceptOrganizationInvitationHandler;
use Zandu\Modules\IdentityAccess\Domain\Access\SystemRoleCatalog;
use Zandu\Modules\IdentityAccess\Domain\Invitation\IntendedRoleAssignment;
use Zandu\Modules\IdentityAccess\Domain\Invitation\InvitationEmail;
use Zandu\Modules\IdentityAccess\Domain\Invitation\InvitationStatus;
use Zandu\Modules\IdentityAccess\Domain\Invitation\OrganizationInvitation;
use Zandu\Modules\IdentityAccess\Domain\Invitation\OrganizationInvitationNotFound;
use Zandu\Modules\IdentityAccess\Domain\Membership\MembershipStatus;
use Zandu\Modules\IdentityAccess\Infrastructure\Persistence\Orm\DoctrineOrganizationInvitationRepository;
use Zandu\Modules\IdentityAccess\Infrastructure\Persistence\Orm\DoctrineOrganizationMembershipRepository;
use Zandu\Modules\IdentityAccess\Infrastructure\Security\SecureInvitationTokenService;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\Platform\Persistence\DoctrineTenantTransaction;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Context\ActorType;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\OrganizationInvitationId;
use Zandu\SharedKernel\Identity\UserId;
use Zandu\SharedKernel\Identity\Uuid;
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\Tests\SharedKernel\Time\FrozenClock;

final class OrganizationInvitationWorkflowTest extends KernelTestCase
{
    private const ORGANIZATION_A = '0198d501-ab09-73bf-b631-c307fd6ed08d';
    private const ORGANIZATION_B = '0198d502-70a2-71df-8beb-b7ae882c8dba';
    private const INVITATION_ID = '0198d503-147c-72d5-b75a-a936797ff9c8';
    private const MEMBERSHIP_ID = '0198d504-147c-72d5-b75a-a936797ff9c8';
    private const ACTOR_ID = '0198d505-1dd7-7c6d-9855-25e5e205940c';
    private const USER_ID = '0198d506-1dd7-7c6d-9855-25e5e205940c';

    private EntityManagerInterface $entityManager;
    private bool $databaseAvailable = false;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        try {
            $this->entityManager->getConnection()->fetchOne('SELECT 1');
            $this->databaseAvailable = true;
        } catch (Throwable $exception) {
            if (is_file('/.dockerenv')) {
                throw $exception;
            } self::markTestSkipped('PostgreSQL integration database is unavailable.');
        }
        $this->deleteFixtures();
        $this->insertOrganization(self::ORGANIZATION_A, 'Tenant A');
        $this->insertOrganization(self::ORGANIZATION_B, 'Tenant B');
    }

    protected function tearDown(): void
    {
        if ($this->databaseAvailable) {
            $this->entityManager->clear();
            $this->deleteFixtures();
        }
        parent::tearDown();
    }

    public function testHashedTokenAcceptanceAndCrossTenantIsolationOnPostgreSql(): void
    {
        $factory = new SymfonyUuidFactory();
        $organizationA = OrganizationId::fromString(self::ORGANIZATION_A, $factory);
        $organizationB = OrganizationId::fromString(self::ORGANIZATION_B, $factory);
        $transactions = new DoctrineTenantTransaction($this->entityManager->getConnection(), 'zandu_runtime');
        $invitations = new DoctrineOrganizationInvitationRepository($this->entityManager, $factory);
        $memberships = new DoctrineOrganizationMembershipRepository($this->entityManager, $factory);
        $tokens = new SecureInvitationTokenService('integration-pepper', $factory);
        $issued = $tokens->issue($organizationA);
        $invitation = OrganizationInvitation::invite(
            OrganizationInvitationId::fromString(self::INVITATION_ID, $factory),
            $organizationA,
            InvitationEmail::fromString('member@example.com'),
            ActorId::fromString(self::ACTOR_ID, $factory),
            $issued->tokenHash(),
            new DateTimeImmutable('2026-08-23T10:00:00+00:00'),
            [IntendedRoleAssignment::forRole('CASHIER')],
            new DateTimeImmutable('2026-08-22T10:00:00+00:00'),
        );
        $transactions->transactional($organizationA, fn() => $invitations->save($invitation));
        $persistedHash = $this->entityManager->getConnection()->fetchOne(
            'SELECT token_hash FROM identity_access.organization_invitations WHERE id = ?',
            [self::INVITATION_ID],
        );
        self::assertSame($issued->tokenHash(), $persistedHash);
        self::assertNotSame($issued->reveal(), $persistedHash);

        $membershipUuid = $factory->fromString(self::MEMBERSHIP_ID);
        $handler = new AcceptOrganizationInvitationHandler(
            $invitations,
            $memberships,
            $tokens,
            new class ($membershipUuid) implements IdGenerator {
                public function __construct(private readonly Uuid $uuid) {}
                public function generate(): Uuid
                {
                    return $this->uuid;
                }
            },
            new FrozenClock(new DateTimeImmutable('2026-08-22T11:00:00+00:00')),
            $transactions,
            new SystemRoleCatalog($factory),
        );
        $membership = $handler(new AcceptOrganizationInvitation($issued->reveal(), $this->actorContext($factory, $organizationB)));
        self::assertSame(self::ORGANIZATION_A, $membership->organizationId()->toString());
        self::assertSame(InvitationStatus::Accepted, $transactions->transactional(
            $organizationA,
            fn() => $invitations->getByTokenHash($organizationA, $issued->tokenHash())->status(),
        ));

        $membership->suspend(ActorId::fromString(self::ACTOR_ID, $factory), new DateTimeImmutable('2026-08-22T12:00:00+00:00'));
        $transactions->transactional($organizationA, fn() => $memberships->save($membership));
        $this->entityManager->clear();
        $restoredMembership = $transactions->transactional(
            $organizationA,
            fn() => $memberships->findByUser($organizationA, UserId::fromString(self::USER_ID, $factory)),
        );
        self::assertSame(MembershipStatus::Suspended, $restoredMembership?->status());
        self::assertSame(2, $restoredMembership?->authorizationVersion());
        self::assertSame(
            (new SystemRoleCatalog($factory))->get(\Zandu\Modules\IdentityAccess\Domain\Access\RoleCode::fromString('CASHIER'))->id()->toString(),
            $restoredMembership?->roleAssignments()[0]->roleId()->toString(),
        );
        self::assertNull($transactions->transactional(
            $organizationB,
            fn() => $memberships->findByUser($organizationA, UserId::fromString(self::USER_ID, $factory)),
        ));

        $this->expectException(OrganizationInvitationNotFound::class);
        $transactions->transactional($organizationB, fn() => $invitations->getByTokenHash($organizationA, $issued->tokenHash()));
    }

    private function actorContext(SymfonyUuidFactory $factory, OrganizationId $currentOrganization): ActorContext
    {
        return new ActorContext(
            ActorId::fromString(self::ACTOR_ID, $factory),
            $currentOrganization,
            ActorType::User,
            CorrelationId::fromString('0198d507-19da-75be-b508-1a4b36cf8d7a', $factory),
            new DateTimeImmutable('2026-08-22T09:00:00+00:00'),
            UserId::fromString(self::USER_ID, $factory),
            null,
            'member@example.com',
        );
    }

    private function insertOrganization(string $id, string $name): void
    {
        $this->entityManager->getConnection()->executeStatement(<<<'SQL'
INSERT INTO organization.organizations (id,name,status,country_code,default_currency,default_time_zone,default_locale,created_by,created_at,updated_by,updated_at,version)
VALUES (?,?,'ACTIVE','CG','XAF','Africa/Brazzaville','fr_CG',?,NOW(),?,NOW(),1)
SQL, [$id, $name, self::ACTOR_ID, self::ACTOR_ID]);
    }

    private function deleteFixtures(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('DELETE FROM identity_access.organization_memberships WHERE organization_id IN (?,?)', [self::ORGANIZATION_A, self::ORGANIZATION_B]);
        $connection->executeStatement('DELETE FROM identity_access.organization_invitations WHERE organization_id IN (?,?)', [self::ORGANIZATION_A, self::ORGANIZATION_B]);
        $connection->executeStatement('DELETE FROM organization.organizations WHERE id IN (?,?)', [self::ORGANIZATION_A, self::ORGANIZATION_B]);
    }
}
