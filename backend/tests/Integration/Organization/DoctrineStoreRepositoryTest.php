<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\Organization;

use DateTimeImmutable;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;
use Zandu\Modules\Organization\Domain\Locale;
use Zandu\Modules\Organization\Domain\Store\Store;
use Zandu\Modules\Organization\Domain\Store\StoreAddress;
use Zandu\Modules\Organization\Domain\Store\StoreCode;
use Zandu\Modules\Organization\Domain\Store\StoreName;
use Zandu\Modules\Organization\Domain\Store\StoreRepository;
use Zandu\Modules\Organization\Domain\Store\StoreStatus;
use Zandu\Modules\Organization\Domain\StoreClosure\StoreClosure;
use Zandu\Modules\Organization\Domain\StoreClosure\StoreClosureNotFound;
use Zandu\Modules\Organization\Domain\StoreClosure\StoreClosureRepository;
use Zandu\Modules\Organization\Domain\StoreClosure\StoreClosureStatus;
use Zandu\Modules\Organization\Domain\TimeZone;
use Zandu\Modules\Organization\Infrastructure\Persistence\Orm\DoctrineStoreClosureRepository;
use Zandu\Modules\Organization\Infrastructure\Persistence\Orm\DoctrineStoreRepository;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\Platform\Persistence\DoctrineTenantTransaction;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\StoreClosureId;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Money\Currency;

final class DoctrineStoreRepositoryTest extends KernelTestCase
{
    private const ORGANIZATION_A = '0198d231-ab09-73bf-b631-c307fd6ed08d';
    private const ORGANIZATION_B = '0198d232-70a2-71df-8beb-b7ae882c8dba';
    private const STORE_A = '0198d233-147c-72d5-b75a-a936797ff9c8';
    private const STORE_A_DUPLICATE = '0198d234-537f-75b8-bd7f-550f88270881';
    private const STORE_B = '0198d235-3765-7eb3-8ef9-f3661c32bc08';
    private const CLOSURE_A = '0198d255-3765-7eb3-8ef9-f3661c32bc08';
    private const ACTOR_ID = '0198d1b2-1dd7-7c6d-9855-25e5e205940c';

    private EntityManagerInterface $entityManager;
    private StoreRepository $repository;
    private StoreClosureRepository $closures;
    private DoctrineTenantTransaction $transactions;
    private bool $databaseAvailable = false;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->repository = new DoctrineStoreRepository($this->entityManager, new SymfonyUuidFactory());
        $this->closures = new DoctrineStoreClosureRepository($this->entityManager, new SymfonyUuidFactory());
        $this->transactions = new DoctrineTenantTransaction($this->entityManager->getConnection(), 'zandu_runtime');

        try {
            $this->entityManager->getConnection()->fetchOne('SELECT 1');
            $this->databaseAvailable = true;
        } catch (Throwable $exception) {
            if (is_file('/.dockerenv')) {
                throw $exception;
            }
            self::markTestSkipped('PostgreSQL integration database is not reachable from this environment.');
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

    public function testStoreRoundTripsWithinItsTenant(): void
    {
        $store = $this->store(self::STORE_A, self::ORGANIZATION_A, 'CENTRE');
        $restored = $this->transactions->transactional(
            $this->organizationId(self::ORGANIZATION_A),
            function () use ($store): Store {
                $this->repository->save($store);
                $store->suspend($this->actorId(), new DateTimeImmutable('2026-08-22T15:00:00+00:00'));
                $this->repository->save($store);
                $this->entityManager->clear();
                return $this->repository->get($store->organizationId(), $store->id());
            },
        );

        self::assertSame(StoreStatus::Suspended, $restored->status());
        self::assertSame('CENTRE', $restored->code()->value());
        self::assertSame(2, $restored->version());
    }

    public function testSameCodeIsAllowedAcrossTenantsButUniqueWithinOneTenant(): void
    {
        $this->transactions->transactional(
            $this->organizationId(self::ORGANIZATION_A),
            fn() => $this->repository->save($this->store(self::STORE_A, self::ORGANIZATION_A, 'CENTRE')),
        );
        $this->entityManager->clear();
        $this->transactions->transactional(
            $this->organizationId(self::ORGANIZATION_B),
            fn() => $this->repository->save($this->store(self::STORE_B, self::ORGANIZATION_B, 'CENTRE')),
        );
        $this->entityManager->clear();

        $this->expectException(UniqueConstraintViolationException::class);
        $this->transactions->transactional(
            $this->organizationId(self::ORGANIZATION_A),
            fn() => $this->repository->save($this->store(self::STORE_A_DUPLICATE, self::ORGANIZATION_A, 'CENTRE')),
        );
    }

    public function testCrossTenantLookupReturnsNothingEvenWithKnownId(): void
    {
        $store = $this->store(self::STORE_B, self::ORGANIZATION_B, 'POTO');
        $this->transactions->transactional(
            $store->organizationId(),
            fn() => $this->repository->save($store),
        );
        $this->entityManager->clear();

        $result = $this->transactions->transactional(
            $this->organizationId(self::ORGANIZATION_A),
            fn(): ?Store => $this->repository->find($store->organizationId(), $store->id()),
        );

        self::assertNull($result);
    }

    public function testStoreClosureRoundTripsAndIsHiddenFromAnotherTenant(): void
    {
        $store = $this->store(self::STORE_A, self::ORGANIZATION_A, 'CENTRE');
        $this->transactions->transactional($store->organizationId(), function () use ($store): void {
            $this->repository->save($store);
            $closure = StoreClosure::request(
                StoreClosureId::fromString(self::CLOSURE_A, new SymfonyUuidFactory()),
                $store->organizationId(),
                $store->id(),
                'Fin du bail',
                $this->actorId(),
                new DateTimeImmutable('2026-08-22T16:00:00+00:00'),
            );
            $closure->evaluate(['OPEN_CASH_SESSION']);
            $this->closures->save($closure);
        });
        $this->entityManager->clear();

        $restored = $this->transactions->transactional(
            $store->organizationId(),
            fn(): StoreClosure => $this->closures->getActiveForStore($store->organizationId(), $store->id()),
        );
        self::assertSame(StoreClosureStatus::InProgress, $restored->status());
        self::assertSame(['OPEN_CASH_SESSION'], $restored->blockers());

        $this->expectException(StoreClosureNotFound::class);
        $this->transactions->transactional(
            $this->organizationId(self::ORGANIZATION_B),
            fn(): StoreClosure => $this->closures->getActiveForStore($store->organizationId(), $store->id()),
        );
    }

    private function store(string $id, string $organizationId, string $code): Store
    {
        $factory = new SymfonyUuidFactory();
        return Store::create(
            StoreId::fromString($id, $factory),
            OrganizationId::fromString($organizationId, $factory),
            StoreCode::fromString($code),
            StoreName::fromString('Store ' . $code),
            StoreAddress::fromString('Brazzaville'),
            TimeZone::fromString('Africa/Brazzaville'),
            Currency::fromCode('XAF'),
            Locale::fromString('fr_CG'),
            Currency::fromCode('XAF'),
            $this->actorId(),
            new DateTimeImmutable('2026-08-22T14:00:00+00:00'),
        );
    }

    private function organizationId(string $id): OrganizationId
    {
        return OrganizationId::fromString($id, new SymfonyUuidFactory());
    }

    private function actorId(): ActorId
    {
        return ActorId::fromString(self::ACTOR_ID, new SymfonyUuidFactory());
    }

    private function insertOrganization(string $id, string $name): void
    {
        $this->entityManager->getConnection()->executeStatement(<<<'SQL'
INSERT INTO organization.organizations (
    id, name, status, country_code, default_currency, default_time_zone,
    default_locale, created_by, created_at, updated_by, updated_at, version
) VALUES (?, ?, 'ACTIVE', 'CG', 'XAF', 'Africa/Brazzaville', 'fr_CG', ?, NOW(), ?, NOW(), 1)
SQL, [$id, $name, self::ACTOR_ID, self::ACTOR_ID]);
    }

    private function deleteFixtures(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement(
            'DELETE FROM organization.store_closures WHERE organization_id IN (?, ?)',
            [self::ORGANIZATION_A, self::ORGANIZATION_B],
        );
        $connection->executeStatement(
            'DELETE FROM organization.stores WHERE organization_id IN (?, ?)',
            [self::ORGANIZATION_A, self::ORGANIZATION_B],
        );
        $connection->executeStatement(
            'DELETE FROM organization.organizations WHERE id IN (?, ?)',
            [self::ORGANIZATION_A, self::ORGANIZATION_B],
        );
    }
}
