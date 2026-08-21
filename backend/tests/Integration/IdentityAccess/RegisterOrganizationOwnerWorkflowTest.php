<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\IdentityAccess;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zandu\Modules\IdentityAccess\Application\Contract\PasswordHasher;
use Zandu\Modules\IdentityAccess\Application\ProvisionInitialOrganizationOwner;
use Zandu\Modules\IdentityAccess\Application\RegisterOrganizationOwner\RegisterOrganizationOwner;
use Zandu\Modules\IdentityAccess\Application\RegisterOrganizationOwner\RegisterOrganizationOwnerHandler;
use Zandu\Modules\IdentityAccess\Domain\Access\SystemRoleCatalog;
use Zandu\Modules\IdentityAccess\Domain\User\UserEmail;
use Zandu\Modules\IdentityAccess\Infrastructure\Persistence\Orm\DoctrineOrganizationMembershipRepository;
use Zandu\Modules\IdentityAccess\Infrastructure\Persistence\Orm\DoctrineUserRepository;
use Zandu\Modules\IdentityAccess\Infrastructure\Security\PersistentUserProvider;
use Zandu\Modules\Organization\Application\OrganizationOnboardingService;
use Zandu\Modules\Organization\Infrastructure\Persistence\Orm\DoctrineOrganizationRepository;
use Zandu\Platform\Auth\Security\AuthenticatedUser;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\Platform\Persistence\DoctrineTenantTransaction;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\UserId;
use Zandu\SharedKernel\Identity\Uuid;
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\Tests\SharedKernel\Time\FrozenClock;

final class RegisterOrganizationOwnerWorkflowTest extends KernelTestCase
{
    private const USER_ID = '0198e001-147c-72d5-b75a-a936797ff9c8';
    private const ACTOR_ID = '0198e002-147c-72d5-b75a-a936797ff9c8';
    private const ORGANIZATION_ID = '0198e003-147c-72d5-b75a-a936797ff9c8';
    private const MEMBERSHIP_ID = '0198e004-147c-72d5-b75a-a936797ff9c8';

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->cleanup();
    }

    protected function tearDown(): void
    {
        $this->cleanup();
        parent::tearDown();
    }

    public function testAccountOrganizationAndOwnerMembershipAreCreatedAtomically(): void
    {
        $factory = new SymfonyUuidFactory();
        $users = new DoctrineUserRepository($this->entityManager, $factory);
        $organizations = new DoctrineOrganizationRepository($this->entityManager, $factory);
        $memberships = new DoctrineOrganizationMembershipRepository($this->entityManager, $factory);
        $catalog = new SystemRoleCatalog($factory);
        $transaction = new DoctrineTenantTransaction($this->entityManager->getConnection(), 'zandu_runtime');
        $generator = new SequentialIdGenerator([
            $factory->fromString(self::USER_ID),
            $factory->fromString(self::ACTOR_ID),
            $factory->fromString(self::ORGANIZATION_ID),
            $factory->fromString(self::MEMBERSHIP_ID),
        ]);
        $handler = new RegisterOrganizationOwnerHandler(
            $users,
            new OrganizationOnboardingService($organizations),
            new ProvisionInitialOrganizationOwner($memberships, $catalog, $generator),
            new class implements PasswordHasher {
                public function hash(string $plainPassword): string
                {
                    return '$argon2id$test-password-hash';
                }
            },
            $generator,
            new FrozenClock(new DateTimeImmutable('2026-08-23T03:00:00+00:00')),
            $transaction,
        );

        $result = $handler(new RegisterOrganizationOwner(
            'Owner@Example.com',
            'a-secure-password',
            'Zandu Onboarding',
            'CG',
            'XAF',
            'Africa/Brazzaville',
            'fr_CG',
            CorrelationId::fromString('0198e005-147c-72d5-b75a-a936797ff9c8', $factory),
        ));
        $membership = $transaction->transactional(
            $result->organizationId,
            fn() => $memberships->findByUser($result->organizationId, UserId::fromString(self::USER_ID, $factory)),
        );

        self::assertSame(self::USER_ID, $result->userId->toString());
        self::assertSame('owner@example.com', $users->findByEmail(UserEmail::fromString('owner@example.com'))?->email()->value());
        self::assertSame('Zandu Onboarding', $transaction->transactional($result->organizationId, fn() => $organizations->get($result->organizationId))->name()->value());
        self::assertTrue($membership?->hasRoleId($catalog->organizationOwnerRoleId()));
        $authenticatedUser = self::getContainer()->get(PersistentUserProvider::class)->loadUserByIdentifier('owner@example.com');
        self::assertInstanceOf(AuthenticatedUser::class, $authenticatedUser);
        self::assertSame(self::ORGANIZATION_ID, $authenticatedUser->organizationId());
    }

    private function cleanup(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('DELETE FROM identity_access.organization_memberships WHERE organization_id = ?', [self::ORGANIZATION_ID]);
        $connection->executeStatement('DELETE FROM organization.organizations WHERE id = ?', [self::ORGANIZATION_ID]);
        $connection->executeStatement('DELETE FROM identity_access.users WHERE id = ?', [self::USER_ID]);
        $this->entityManager->clear();
    }
}

final class SequentialIdGenerator implements IdGenerator
{
    /** @param non-empty-list<Uuid> $uuids */
    public function __construct(private array $uuids) {}
    public function generate(): Uuid
    {
        return array_shift($this->uuids) ?? throw new \LogicException('No UUID left.');
    }
}
