<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\Organization;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;
use Zandu\Modules\IdentityAccess\Application\ProvisionInitialOrganizationOwner;
use Zandu\Modules\IdentityAccess\Domain\Access\SystemRoleCatalog;
use Zandu\Modules\IdentityAccess\Infrastructure\Persistence\Orm\DoctrineOrganizationMembershipRepository;
use Zandu\Modules\Organization\Application\CreateOrganization\CreateOrganization;
use Zandu\Modules\Organization\Application\CreateOrganization\CreateOrganizationHandler;
use Zandu\Modules\Organization\Domain\CountryCode;
use Zandu\Modules\Organization\Domain\Locale;
use Zandu\Modules\Organization\Domain\Organization;
use Zandu\Modules\Organization\Domain\OrganizationName;
use Zandu\Modules\Organization\Domain\OrganizationRepository;
use Zandu\Modules\Organization\Domain\OrganizationStatus;
use Zandu\Modules\Organization\Domain\TimeZone;
use Zandu\Modules\Organization\Infrastructure\Persistence\Orm\DoctrineOrganizationRepository;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\Platform\Persistence\DoctrineTenantTransaction;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Context\ActorType;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\UserId;
use Zandu\SharedKernel\Identity\Uuid;
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\Money\Currency;
use Zandu\Tests\SharedKernel\Time\FrozenClock;

final class DoctrineOrganizationRepositoryTest extends KernelTestCase
{
    private const ORGANIZATION_ID = '0198d1b1-b2a4-7b6e-8e0e-608484906502';
    private const ACTOR_ID = '0198d1b2-1dd7-7c6d-9855-25e5e205940c';
    private const USER_ID = '0198d1b3-1dd7-7c6d-9855-25e5e205940c';
    private const MEMBERSHIP_ID = '0198d1b4-1dd7-7c6d-9855-25e5e205940c';

    private EntityManagerInterface $entityManager;
    private OrganizationRepository $repository;
    private bool $databaseAvailable = false;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->repository = new DoctrineOrganizationRepository(
            $this->entityManager,
            new SymfonyUuidFactory(),
        );

        try {
            $this->entityManager->getConnection()->fetchOne('SELECT 1');
            $this->databaseAvailable = true;
        } catch (Throwable $exception) {
            if (is_file('/.dockerenv')) {
                throw $exception;
            }

            self::markTestSkipped('PostgreSQL integration database is not reachable from this environment.');
        }

        $this->entityManager->getConnection()->executeStatement(
            'DELETE FROM identity_access.organization_memberships WHERE organization_id = ?',
            [self::ORGANIZATION_ID],
        );
        $this->entityManager->getConnection()->executeStatement(
            'DELETE FROM organization.organizations WHERE id = ?',
            [self::ORGANIZATION_ID],
        );
    }

    protected function tearDown(): void
    {
        if (isset($this->entityManager) && $this->databaseAvailable) {
            $this->entityManager->getConnection()->executeStatement(
                'DELETE FROM identity_access.organization_memberships WHERE organization_id = ?',
                [self::ORGANIZATION_ID],
            );
            $this->entityManager->getConnection()->executeStatement(
                'DELETE FROM organization.organizations WHERE id = ?',
                [self::ORGANIZATION_ID],
            );
            $this->entityManager->clear();
        }

        parent::tearDown();
    }

    public function testAggregateRoundTripsThroughPostgreSql(): void
    {
        $organization = $this->organization();
        $restored = (new DoctrineTenantTransaction(
            $this->entityManager->getConnection(),
            'zandu_runtime',
        ))->transactional(
            $organization->id(),
            function () use ($organization): Organization {
                $this->repository->save($organization);
                $organization->suspend($this->actorId(), new DateTimeImmutable('2026-08-22T10:00:00+00:00'));
                $this->repository->save($organization);
                $this->entityManager->clear();

                return $this->repository->get($organization->id());
            },
        );

        self::assertSame(self::ORGANIZATION_ID, $restored->id()->toString());
        self::assertSame('Zandu', $restored->name()->value());
        self::assertSame(OrganizationStatus::Suspended, $restored->status());
        self::assertSame('XAF', $restored->defaultCurrency()->code());
        self::assertSame(2, $restored->version());
        self::assertSame([], $restored->releaseEvents());
    }

    public function testCreatingOrganizationAlsoCreatesItsInitialOwner(): void
    {
        $factory = new SymfonyUuidFactory();
        $uuids = [
            $factory->fromString(self::ORGANIZATION_ID),
            $factory->fromString(self::MEMBERSHIP_ID),
        ];
        $generator = new class ($uuids) implements IdGenerator {
            /** @param non-empty-list<Uuid> $uuids */
            public function __construct(private array $uuids) {}
            public function generate(): Uuid
            {
                return array_shift($this->uuids) ?? throw new \LogicException('No UUID left.');
            }
        };
        $memberships = new DoctrineOrganizationMembershipRepository($this->entityManager, $factory);
        $catalog = new SystemRoleCatalog($factory);
        $transaction = new DoctrineTenantTransaction($this->entityManager->getConnection(), 'zandu_runtime');
        $handler = new CreateOrganizationHandler(
            $this->repository,
            $generator,
            new FrozenClock(new DateTimeImmutable('2026-08-22T08:00:00+00:00')),
            $transaction,
            new ProvisionInitialOrganizationOwner($memberships, $catalog, $generator),
        );
        $context = new ActorContext(
            $this->actorId(),
            OrganizationId::fromString(self::ORGANIZATION_ID, $factory),
            ActorType::User,
            CorrelationId::fromString('0198d1b5-1dd7-7c6d-9855-25e5e205940c', $factory),
            new DateTimeImmutable('2026-08-22T07:00:00+00:00'),
            UserId::fromString(self::USER_ID, $factory),
        );

        $organization = $handler(new CreateOrganization('Zandu', 'CG', 'XAF', 'Africa/Brazzaville', 'fr_CG', $context));
        $membership = $transaction->transactional(
            $organization->id(),
            fn() => $memberships->findByUser($organization->id(), UserId::fromString(self::USER_ID, $factory)),
        );

        self::assertNotNull($membership);
        self::assertTrue($membership->hasRoleId($catalog->organizationOwnerRoleId()));
    }

    private function organization(): Organization
    {
        return Organization::create(
            OrganizationId::fromString(self::ORGANIZATION_ID, new SymfonyUuidFactory()),
            OrganizationName::fromString('Zandu'),
            CountryCode::fromString('CG'),
            Currency::fromCode('XAF'),
            TimeZone::fromString('Africa/Brazzaville'),
            Locale::fromString('fr_CG'),
            $this->actorId(),
            new DateTimeImmutable('2026-08-22T08:00:00+00:00'),
        );
    }

    private function actorId(): ActorId
    {
        return ActorId::fromString(self::ACTOR_ID, new SymfonyUuidFactory());
    }
}
