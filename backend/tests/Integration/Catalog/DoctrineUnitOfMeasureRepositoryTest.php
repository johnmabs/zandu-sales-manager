<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\Catalog;

use DateTimeImmutable;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;
use Zandu\Modules\Catalog\Domain\UnitOfMeasure;
use Zandu\Modules\Catalog\Domain\UnitOfMeasureCode;
use Zandu\Modules\Catalog\Domain\UnitOfMeasureDimension;
use Zandu\Modules\Catalog\Domain\UnitOfMeasureName;
use Zandu\Modules\Catalog\Domain\UnitOfMeasurePrecision;
use Zandu\Modules\Catalog\Domain\UnitOfMeasureRepository;
use Zandu\Modules\Catalog\Domain\UnitOfMeasureStatus;
use Zandu\Modules\Catalog\Infrastructure\Persistence\Orm\DoctrineUnitOfMeasureRepository;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\Platform\Persistence\DoctrineTenantTransaction;
use Zandu\SharedKernel\Decimal\RoundingMode;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\UnitOfMeasureId;

final class DoctrineUnitOfMeasureRepositoryTest extends KernelTestCase
{
    private const ORGANIZATION_A = '0198d271-ab09-73bf-b631-c307fd6ed08d';
    private const ORGANIZATION_B = '0198d272-70a2-71df-8beb-b7ae882c8dba';
    private const UNIT_A = '0198d273-147c-72d5-b75a-a936797ff9c8';
    private const UNIT_A_DUPLICATE = '0198d274-537f-75b8-bd7f-550f88270881';
    private const UNIT_B = '0198d275-3765-7eb3-8ef9-f3661c32bc08';
    private const ACTOR_ID = '0198d1b2-1dd7-7c6d-9855-25e5e205940c';

    private EntityManagerInterface $entityManager;
    private UnitOfMeasureRepository $repository;
    private DoctrineTenantTransaction $transactions;
    private bool $databaseAvailable = false;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->repository = new DoctrineUnitOfMeasureRepository($this->entityManager, new SymfonyUuidFactory());
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
        $this->insertOrganization(self::ORGANIZATION_A, 'Catalog tenant A');
        $this->insertOrganization(self::ORGANIZATION_B, 'Catalog tenant B');
    }

    protected function tearDown(): void
    {
        if ($this->databaseAvailable) {
            $this->entityManager->clear();
            $this->deleteFixtures();
        }

        parent::tearDown();
    }

    public function testUnitRoundTripsAndUpdatesWithinItsTenant(): void
    {
        $unit = $this->unit(self::UNIT_A, self::ORGANIZATION_A, 'KG');

        $restored = $this->transactions->transactional(
            $unit->organizationId(),
            function () use ($unit): UnitOfMeasure {
                $this->repository->save($unit);
                $unit->update(
                    UnitOfMeasureName::fromString('Kilogramme net'),
                    UnitOfMeasureDimension::Mass,
                    UnitOfMeasurePrecision::fromInt(4),
                    RoundingMode::HalfEven,
                    $this->actorId(),
                    new DateTimeImmutable('2026-08-25T15:00:00Z'),
                );
                $this->repository->save($unit);
                $this->entityManager->clear();

                return $this->repository->get($unit->organizationId(), $unit->id());
            },
        );

        self::assertSame('KG', $restored->code()->value());
        self::assertSame('Kilogramme net', $restored->name()->value());
        self::assertSame(4, $restored->precision()->value());
        self::assertSame(RoundingMode::HalfEven, $restored->roundingMode());
        self::assertSame(2, $restored->version());
    }

    public function testSameCodeIsAllowedAcrossTenantsButUniqueWithinOneTenant(): void
    {
        $this->transactions->transactional(
            $this->organizationId(self::ORGANIZATION_A),
            fn() => $this->repository->save($this->unit(self::UNIT_A, self::ORGANIZATION_A, 'EA')),
        );
        $this->entityManager->clear();
        $this->transactions->transactional(
            $this->organizationId(self::ORGANIZATION_B),
            fn() => $this->repository->save($this->unit(self::UNIT_B, self::ORGANIZATION_B, 'EA')),
        );
        $this->entityManager->clear();

        $this->expectException(UniqueConstraintViolationException::class);
        $this->transactions->transactional(
            $this->organizationId(self::ORGANIZATION_A),
            fn() => $this->repository->save($this->unit(self::UNIT_A_DUPLICATE, self::ORGANIZATION_A, 'EA')),
        );
    }

    public function testRlsHidesAUnitFromAnotherTenant(): void
    {
        $unit = $this->unit(self::UNIT_B, self::ORGANIZATION_B, 'L');
        $this->transactions->transactional(
            $unit->organizationId(),
            fn() => $this->repository->save($unit),
        );
        $this->entityManager->clear();

        $result = $this->transactions->transactional(
            $this->organizationId(self::ORGANIZATION_A),
            fn(): ?UnitOfMeasure => $this->repository->find($unit->organizationId(), $unit->id()),
        );

        self::assertNull($result);
    }

    public function testRepositoryRejectsAStaleAggregate(): void
    {
        $unit = $this->unit(self::UNIT_A, self::ORGANIZATION_A, 'KG');
        $this->transactions->transactional(
            $unit->organizationId(),
            fn() => $this->repository->save($unit),
        );
        $this->entityManager->clear();

        $stale = $this->transactions->transactional(
            $unit->organizationId(),
            fn(): UnitOfMeasure => $this->repository->get($unit->organizationId(), $unit->id()),
        );
        $this->entityManager->clear();
        $this->entityManager->getConnection()->executeStatement(
            'UPDATE catalog.units_of_measure SET version = version + 1 WHERE id = ?',
            [self::UNIT_A],
        );
        $stale->deactivate($this->actorId(), new DateTimeImmutable('2026-08-25T16:00:00Z'));

        $this->expectException(OptimisticLockException::class);
        $this->transactions->transactional(
            $stale->organizationId(),
            fn() => $this->repository->save($stale),
        );
    }

    public function testFindAllIsTenantScopedAndOrderedByName(): void
    {
        $organizationId = $this->organizationId(self::ORGANIZATION_A);
        $this->transactions->transactional($organizationId, function (): void {
            $this->repository->save($this->unit(self::UNIT_A, self::ORGANIZATION_A, 'KG', 'Z kilogramme'));
            $this->repository->save($this->unit(self::UNIT_A_DUPLICATE, self::ORGANIZATION_A, 'EA', 'Article'));
        });
        $this->entityManager->clear();

        $units = $this->transactions->transactional(
            $organizationId,
            fn(): array => $this->repository->findAll($organizationId),
        );

        self::assertSame(['Article', 'Z kilogramme'], array_map(
            static fn(UnitOfMeasure $unit): string => $unit->name()->value(),
            $units,
        ));
        self::assertTrue($this->transactions->transactional(
            $organizationId,
            fn(): bool => $this->repository->codeExists($organizationId, UnitOfMeasureCode::fromString('KG')),
        ));
    }

    private function unit(string $id, string $organizationId, string $code, string $name = 'Kilogramme'): UnitOfMeasure
    {
        $factory = new SymfonyUuidFactory();

        return UnitOfMeasure::create(
            UnitOfMeasureId::fromString($id, $factory),
            OrganizationId::fromString($organizationId, $factory),
            UnitOfMeasureCode::fromString($code),
            UnitOfMeasureName::fromString($name),
            UnitOfMeasureDimension::Mass,
            UnitOfMeasurePrecision::fromInt(3),
            RoundingMode::HalfUp,
            $this->actorId(),
            new DateTimeImmutable('2026-08-25T14:00:00Z'),
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
            'DELETE FROM catalog.units_of_measure WHERE organization_id IN (?, ?)',
            [self::ORGANIZATION_A, self::ORGANIZATION_B],
        );
        $connection->executeStatement(
            'DELETE FROM organization.organizations WHERE id IN (?, ?)',
            [self::ORGANIZATION_A, self::ORGANIZATION_B],
        );
    }
}
