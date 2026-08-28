<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\Purchasing;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;
use Zandu\Modules\Purchasing\Domain\Supplier\Supplier;
use Zandu\Modules\Purchasing\Domain\Supplier\SupplierName;
use Zandu\Modules\Purchasing\Domain\Supplier\SupplierRepository;
use Zandu\Modules\Purchasing\Domain\Supplier\SupplierStatus;
use Zandu\Modules\Purchasing\Infrastructure\Persistence\Orm\DoctrineSupplierRepository;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\Platform\Persistence\DoctrineTenantTransaction;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\SupplierId;

final class DoctrineSupplierRepositoryTest extends KernelTestCase
{
    private const ORGANIZATION_A = '0198d911-ab09-73bf-b631-c307fd6ed08d';
    private const ORGANIZATION_B = '0198d912-70a2-71df-8beb-b7ae882c8dba';
    private const SUPPLIER_A = '0198d913-147c-72d5-b75a-a936797ff9c8';
    private const SUPPLIER_B = '0198d914-537f-75b8-bd7f-550f88270881';
    private const ACTOR_ID = '0198d1b2-1dd7-7c6d-9855-25e5e205940c';

    private EntityManagerInterface $entityManager;
    private SupplierRepository $repository;
    private DoctrineTenantTransaction $transactions;
    private bool $databaseAvailable = false;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->repository = new DoctrineSupplierRepository($this->entityManager, new SymfonyUuidFactory());
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
        $this->insertOrganization(self::ORGANIZATION_A, 'Purchasing tenant A');
        $this->insertOrganization(self::ORGANIZATION_B, 'Purchasing tenant B');
    }

    protected function tearDown(): void
    {
        if ($this->databaseAvailable) {
            $this->entityManager->clear();
            $this->deleteFixtures();
        }
        parent::tearDown();
    }

    public function testSupplierRoundTripsAndUpdatesWithinItsTenant(): void
    {
        $supplier = $this->supplier(self::SUPPLIER_A, self::ORGANIZATION_A, 'Acme');
        $restored = $this->transactions->transactional($supplier->organizationId(), function () use ($supplier): Supplier {
            $this->repository->save($supplier);
            $supplier->update(SupplierName::fromString('Acme RDC'), null, 'contact@acme.example', null, null, $this->actorId(), new DateTimeImmutable('2026-08-28T20:00:00Z'));
            $this->repository->save($supplier);
            $this->entityManager->clear();

            return $this->repository->get($supplier->organizationId(), $supplier->id());
        });

        self::assertSame('Acme RDC', $restored->name()->value());
        self::assertSame('contact@acme.example', $restored->email());
        self::assertSame(SupplierStatus::Active, $restored->status());
        self::assertSame(2, $restored->version());
    }

    public function testRlsHidesASupplierFromAnotherTenant(): void
    {
        $supplier = $this->supplier(self::SUPPLIER_B, self::ORGANIZATION_B, 'Tenant B');
        $this->transactions->transactional($supplier->organizationId(), fn() => $this->repository->save($supplier));
        $this->entityManager->clear();

        $result = $this->transactions->transactional(
            $this->organizationId(self::ORGANIZATION_A),
            fn(): ?Supplier => $this->repository->find($supplier->organizationId(), $supplier->id()),
        );

        self::assertNull($result);
    }

    public function testRepositoryRejectsAStaleSupplier(): void
    {
        $supplier = $this->supplier(self::SUPPLIER_A, self::ORGANIZATION_A, 'Acme');
        $this->transactions->transactional($supplier->organizationId(), fn() => $this->repository->save($supplier));
        $this->entityManager->clear();
        $stale = $this->transactions->transactional(
            $supplier->organizationId(),
            fn(): Supplier => $this->repository->get($supplier->organizationId(), $supplier->id()),
        );
        $this->entityManager->clear();
        $this->entityManager->getConnection()->executeStatement(
            'UPDATE purchasing.supplier SET version = version + 1 WHERE id = ?',
            [self::SUPPLIER_A],
        );
        $stale->deactivate($this->actorId(), new DateTimeImmutable('2026-08-28T21:00:00Z'));

        $this->expectException(OptimisticLockException::class);
        $this->transactions->transactional($stale->organizationId(), fn() => $this->repository->save($stale));
    }

    public function testFindAllIsTenantScopedAndOrderedByName(): void
    {
        $organizationId = $this->organizationId(self::ORGANIZATION_A);
        $this->transactions->transactional($organizationId, function (): void {
            $this->repository->save($this->supplier(self::SUPPLIER_A, self::ORGANIZATION_A, 'Zulu'));
            $this->repository->save($this->supplier(self::SUPPLIER_B, self::ORGANIZATION_A, 'Alpha'));
        });
        $this->entityManager->clear();

        $suppliers = $this->transactions->transactional($organizationId, fn(): array => $this->repository->findAll($organizationId));

        self::assertSame(['Alpha', 'Zulu'], array_map(static fn(Supplier $supplier): string => $supplier->name()->value(), $suppliers));
    }

    private function supplier(string $id, string $organizationId, string $name): Supplier
    {
        $factory = new SymfonyUuidFactory();

        return Supplier::create(
            SupplierId::fromString($id, $factory),
            OrganizationId::fromString($organizationId, $factory),
            SupplierName::fromString($name),
            null,
            null,
            null,
            null,
            $this->actorId(),
            new DateTimeImmutable('2026-08-28T19:00:00Z'),
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
            'DELETE FROM purchasing.supplier WHERE organization_id IN (?, ?)',
            [self::ORGANIZATION_A, self::ORGANIZATION_B],
        );
        $connection->executeStatement(
            'DELETE FROM organization.organizations WHERE id IN (?, ?)',
            [self::ORGANIZATION_A, self::ORGANIZATION_B],
        );
    }
}
