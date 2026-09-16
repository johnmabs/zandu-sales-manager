<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\Concurrency;

use DateTimeImmutable;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;
use Zandu\Modules\IdentityAccess\Domain\Access\AccessScope;
use Zandu\Modules\IdentityAccess\Domain\Access\RoleAssignment;
use Zandu\Modules\IdentityAccess\Domain\Invitation\IntendedRoleAssignment;
use Zandu\Modules\IdentityAccess\Domain\Invitation\InvitationEmail;
use Zandu\Modules\IdentityAccess\Domain\Invitation\OrganizationInvitation;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembership;
use Zandu\Modules\IdentityAccess\Infrastructure\Persistence\Orm\DoctrineOrganizationInvitationRepository;
use Zandu\Modules\IdentityAccess\Infrastructure\Persistence\Orm\DoctrineOrganizationMembershipRepository;
use Zandu\Modules\Organization\Domain\Locale;
use Zandu\Modules\Organization\Domain\Store\Store;
use Zandu\Modules\Organization\Domain\Store\StoreAddress;
use Zandu\Modules\Organization\Domain\Store\StoreCode;
use Zandu\Modules\Organization\Domain\Store\StoreName;
use Zandu\Modules\Organization\Domain\TimeZone;
use Zandu\Modules\Organization\Infrastructure\Persistence\Orm\DoctrineStoreRepository;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\Platform\Persistence\DoctrineTenantTransaction;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\OrganizationInvitationId;
use Zandu\SharedKernel\Identity\OrganizationMembershipId;
use Zandu\SharedKernel\Identity\RoleId;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Identity\UserId;
use Zandu\SharedKernel\Money\Currency;

final class AdministrativeAggregateConcurrencyTest extends KernelTestCase
{
    private const ORGANIZATION_ID = '0199f101-1111-7111-8111-111111111111';
    private const STORE_ID = '0199f102-1111-7111-8111-111111111111';
    private const MEMBERSHIP_ID = '0199f103-1111-7111-8111-111111111111';
    private const INVITATION_ID = '0199f104-1111-7111-8111-111111111111';
    private const ACTOR_ID = '0199f105-1111-7111-8111-111111111111';
    private const USER_ID = '0199f106-1111-7111-8111-111111111111';
    private const ROLE_ID = '0199f107-1111-7111-8111-111111111111';

    private EntityManagerInterface $primaryEntityManager;
    private EntityManagerInterface $secondaryEntityManager;
    private bool $databaseAvailable = false;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->primaryEntityManager = self::getContainer()->get(EntityManagerInterface::class);

        try {
            $this->primaryEntityManager->getConnection()->fetchOne('SELECT 1');
            $this->databaseAvailable = true;
        } catch (Throwable $exception) {
            if (is_file('/.dockerenv')) {
                throw $exception;
            }

            self::markTestSkipped('PostgreSQL integration database is not reachable from this environment.');
        }

        $secondConnection = DriverManager::getConnection($this->primaryEntityManager->getConnection()->getParams());
        $this->secondaryEntityManager = new EntityManager(
            $secondConnection,
            $this->primaryEntityManager->getConfiguration(),
        );

        $this->deleteFixtures();
        $this->primaryEntityManager->getConnection()->executeStatement(<<<'SQL'
INSERT INTO organization.organizations (
    id, name, status, country_code, default_currency, default_time_zone,
    default_locale, created_by, created_at, updated_by, updated_at, version
) VALUES (?, 'Concurrency tenant', 'ACTIVE', 'CG', 'XAF', 'Africa/Brazzaville', 'fr_CG', ?, NOW(), ?, NOW(), 1)
SQL, [self::ORGANIZATION_ID, self::ACTOR_ID, self::ACTOR_ID]);
    }

    protected function tearDown(): void
    {
        if ($this->databaseAvailable) {
            $this->primaryEntityManager->clear();
            $this->deleteFixtures();
            $this->secondaryEntityManager->getConnection()->close();
        }

        parent::tearDown();
    }

    public function testStoreRejectsTheSecondConcurrentUpdate(): void
    {
        $factory = new SymfonyUuidFactory();
        $organizationId = $this->organizationId();
        $primaryRepository = new DoctrineStoreRepository($this->primaryEntityManager, $factory);
        $secondaryRepository = new DoctrineStoreRepository($this->secondaryEntityManager, $factory);
        $primaryTransaction = $this->transaction($this->primaryEntityManager);
        $secondaryTransaction = $this->transaction($this->secondaryEntityManager);
        $store = Store::create(
            StoreId::fromString(self::STORE_ID, $factory),
            $organizationId,
            StoreCode::fromString('MAIN'),
            StoreName::fromString('Main store'),
            StoreAddress::fromString('Brazzaville'),
            TimeZone::fromString('Africa/Brazzaville'),
            Currency::fromCode('XAF'),
            Locale::fromString('fr_CG'),
            Currency::fromCode('XAF'),
            $this->actorId(),
            new DateTimeImmutable('2026-09-16T10:00:00Z'),
        );
        $primaryTransaction->transactional($organizationId, fn() => $primaryRepository->save($store));
        $this->primaryEntityManager->clear();

        $first = $primaryTransaction->transactional($organizationId, fn(): Store => $primaryRepository->get($organizationId, $store->id()));
        $second = $secondaryTransaction->transactional($organizationId, fn(): Store => $secondaryRepository->get($organizationId, $store->id()));

        $first->update(StoreName::fromString('First writer'), $first->address(), $first->timeZone(), $first->locale(), $this->actorId(), new DateTimeImmutable('2026-09-16T10:01:00Z'));
        $primaryTransaction->transactional($organizationId, fn() => $primaryRepository->save($first));

        $second->update(StoreName::fromString('Second writer'), $second->address(), $second->timeZone(), $second->locale(), $this->actorId(), new DateTimeImmutable('2026-09-16T10:02:00Z'));
        $this->assertOptimisticLockFailure(fn() => $secondaryTransaction->transactional($organizationId, fn() => $secondaryRepository->save($second)));

        self::assertSame(
            ['First writer', 2],
            $this->primaryEntityManager->getConnection()->fetchNumeric('SELECT name, version FROM organization.stores WHERE id = ?', [self::STORE_ID]),
        );
    }

    public function testMembershipRejectsTheSecondConcurrentLifecycleChange(): void
    {
        $factory = new SymfonyUuidFactory();
        $organizationId = $this->organizationId();
        $primaryRepository = new DoctrineOrganizationMembershipRepository($this->primaryEntityManager, $factory);
        $secondaryRepository = new DoctrineOrganizationMembershipRepository($this->secondaryEntityManager, $factory);
        $primaryTransaction = $this->transaction($this->primaryEntityManager);
        $secondaryTransaction = $this->transaction($this->secondaryEntityManager);
        $membership = OrganizationMembership::activateFromInvitation(
            OrganizationMembershipId::fromString(self::MEMBERSHIP_ID, $factory),
            $organizationId,
            UserId::fromString(self::USER_ID, $factory),
            [RoleAssignment::assign(
                RoleId::fromString(self::ROLE_ID, $factory),
                AccessScope::organization($organizationId),
                $this->actorId(),
                new DateTimeImmutable('2026-09-16T10:00:00Z'),
            )],
            $this->actorId(),
            new DateTimeImmutable('2026-09-16T10:00:00Z'),
        );
        $primaryTransaction->transactional($organizationId, fn() => $primaryRepository->save($membership));
        $this->primaryEntityManager->clear();

        $first = $primaryTransaction->transactional($organizationId, fn(): OrganizationMembership => $primaryRepository->get($organizationId, $membership->id()));
        $second = $secondaryTransaction->transactional($organizationId, fn(): OrganizationMembership => $secondaryRepository->get($organizationId, $membership->id()));

        $first->suspend($this->actorId(), new DateTimeImmutable('2026-09-16T10:01:00Z'));
        $primaryTransaction->transactional($organizationId, fn() => $primaryRepository->save($first));

        $second->revoke($this->actorId(), new DateTimeImmutable('2026-09-16T10:02:00Z'));
        $this->assertOptimisticLockFailure(fn() => $secondaryTransaction->transactional($organizationId, fn() => $secondaryRepository->save($second)));

        self::assertSame(
            ['SUSPENDED', 2, 2],
            $this->primaryEntityManager->getConnection()->fetchNumeric('SELECT status, authorization_version, version FROM identity_access.organization_memberships WHERE id = ?', [self::MEMBERSHIP_ID]),
        );
    }

    public function testInvitationRejectsTheSecondConcurrentTerminalTransition(): void
    {
        $factory = new SymfonyUuidFactory();
        $organizationId = $this->organizationId();
        $primaryRepository = new DoctrineOrganizationInvitationRepository($this->primaryEntityManager, $factory);
        $secondaryRepository = new DoctrineOrganizationInvitationRepository($this->secondaryEntityManager, $factory);
        $primaryTransaction = $this->transaction($this->primaryEntityManager);
        $secondaryTransaction = $this->transaction($this->secondaryEntityManager);
        $invitation = OrganizationInvitation::invite(
            OrganizationInvitationId::fromString(self::INVITATION_ID, $factory),
            $organizationId,
            InvitationEmail::fromString('concurrent@example.com'),
            $this->actorId(),
            hash('sha256', 'concurrency-token'),
            new DateTimeImmutable('2026-09-20T10:00:00Z'),
            [IntendedRoleAssignment::forRole('CASHIER')],
            new DateTimeImmutable('2026-09-16T10:00:00Z'),
        );
        $primaryTransaction->transactional($organizationId, fn() => $primaryRepository->save($invitation));
        $this->primaryEntityManager->clear();

        $first = $primaryTransaction->transactional($organizationId, fn(): OrganizationInvitation => $primaryRepository->get($organizationId, $invitation->id()));
        $second = $secondaryTransaction->transactional($organizationId, fn(): OrganizationInvitation => $secondaryRepository->get($organizationId, $invitation->id()));

        $first->cancel(new DateTimeImmutable('2026-09-16T10:01:00Z'));
        $primaryTransaction->transactional($organizationId, fn() => $primaryRepository->save($first));

        $second->accept(UserId::fromString(self::USER_ID, $factory), $this->actorId(), new DateTimeImmutable('2026-09-16T10:02:00Z'));
        $this->assertOptimisticLockFailure(fn() => $secondaryTransaction->transactional($organizationId, fn() => $secondaryRepository->save($second)));

        self::assertSame(
            ['CANCELLED', 2],
            $this->primaryEntityManager->getConnection()->fetchNumeric('SELECT status, version FROM identity_access.organization_invitations WHERE id = ?', [self::INVITATION_ID]),
        );
    }

    private function assertOptimisticLockFailure(callable $operation): void
    {
        try {
            $operation();
            self::fail('The second connection must not persist a stale aggregate.');
        } catch (OptimisticLockException) {
            self::addToAssertionCount(1);
        }
    }

    private function transaction(EntityManagerInterface $entityManager): DoctrineTenantTransaction
    {
        return new DoctrineTenantTransaction($entityManager->getConnection(), 'zandu_runtime');
    }

    private function organizationId(): OrganizationId
    {
        return OrganizationId::fromString(self::ORGANIZATION_ID, new SymfonyUuidFactory());
    }

    private function actorId(): ActorId
    {
        return ActorId::fromString(self::ACTOR_ID, new SymfonyUuidFactory());
    }

    private function deleteFixtures(): void
    {
        $connection = $this->primaryEntityManager->getConnection();
        $connection->executeStatement('DELETE FROM identity_access.organization_memberships WHERE organization_id = ?', [self::ORGANIZATION_ID]);
        $connection->executeStatement('DELETE FROM identity_access.organization_invitations WHERE organization_id = ?', [self::ORGANIZATION_ID]);
        $connection->executeStatement('DELETE FROM organization.stores WHERE organization_id = ?', [self::ORGANIZATION_ID]);
        $connection->executeStatement('DELETE FROM organization.organizations WHERE id = ?', [self::ORGANIZATION_ID]);
    }
}
