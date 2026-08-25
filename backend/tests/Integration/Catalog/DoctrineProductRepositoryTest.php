<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\Catalog;

use DateTimeImmutable;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;
use Zandu\Modules\Catalog\Domain\Product\Product;
use Zandu\Modules\Catalog\Domain\Product\ProductCode;
use Zandu\Modules\Catalog\Domain\Product\ProductName;
use Zandu\Modules\Catalog\Domain\Product\ProductRepository;
use Zandu\Modules\Catalog\Domain\Product\ProductStatus;
use Zandu\Modules\Catalog\Domain\Product\ProductType;
use Zandu\Modules\Catalog\Infrastructure\Persistence\Orm\DoctrineProductRepository;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\Platform\Persistence\DoctrineTenantTransaction;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\CategoryId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\TaxCategoryId;
use Zandu\SharedKernel\Identity\UnitOfMeasureId;

final class DoctrineProductRepositoryTest extends KernelTestCase
{
    private const ORGANIZATION_A = '0198d2a1-ab09-73bf-b631-c307fd6ed08d';
    private const ORGANIZATION_B = '0198d2a2-70a2-71df-8beb-b7ae882c8dba';
    private const UNIT_A = '0198d2a3-147c-72d5-b75a-a936797ff9c8';
    private const UNIT_B = '0198d2a4-537f-75b8-bd7f-550f88270881';
    private const CATEGORY_A = '0198d2a5-3765-7eb3-8ef9-f3661c32bc08';
    private const CATEGORY_B = '0198d2a6-3765-7eb3-8ef9-f3661c32bc08';
    private const PRODUCT_A = '0198d2a7-3765-7eb3-8ef9-f3661c32bc08';
    private const PRODUCT_A_DUPLICATE = '0198d2a8-3765-7eb3-8ef9-f3661c32bc08';
    private const PRODUCT_B = '0198d2a9-3765-7eb3-8ef9-f3661c32bc08';
    private const TAX_CATEGORY = '0198d2aa-3765-7eb3-8ef9-f3661c32bc08';
    private const ACTOR_ID = '0198d1b2-1dd7-7c6d-9855-25e5e205940c';

    private EntityManagerInterface $entityManager;
    private ProductRepository $repository;
    private DoctrineTenantTransaction $transactions;
    private bool $databaseAvailable = false;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->repository = new DoctrineProductRepository($this->entityManager, new SymfonyUuidFactory());
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
        $this->insertOrganization(self::ORGANIZATION_A, 'Product tenant A');
        $this->insertOrganization(self::ORGANIZATION_B, 'Product tenant B');
        $this->insertUnit(self::UNIT_A, self::ORGANIZATION_A, 'EA');
        $this->insertUnit(self::UNIT_B, self::ORGANIZATION_B, 'EA');
        $this->insertCategory(self::CATEGORY_A, self::ORGANIZATION_A, 'Tenant A category');
        $this->insertCategory(self::CATEGORY_B, self::ORGANIZATION_B, 'Tenant B category');
    }

    protected function tearDown(): void
    {
        if ($this->databaseAvailable) {
            $this->entityManager->clear();
            $this->deleteFixtures();
        }

        parent::tearDown();
    }

    public function testProductRoundTripsAndUpdatesWithOptimisticVersion(): void
    {
        $product = $this->product(self::PRODUCT_A, self::ORGANIZATION_A, self::UNIT_A, self::CATEGORY_A);
        $organizationId = $product->organizationId();

        $restored = $this->transactions->transactional($organizationId, function () use ($product): Product {
            $this->repository->save($product);
            $product->activate(true, $this->actorId(), new DateTimeImmutable('2026-08-25T12:30:00Z'));
            $this->repository->save($product);
            $this->entityManager->clear();

            return $this->repository->get($product->organizationId(), $product->id());
        });

        self::assertSame(ProductStatus::Active, $restored->status());
        self::assertSame(2, $restored->version());
        self::assertSame(self::TAX_CATEGORY, $restored->taxCategoryId()?->toString());
        self::assertSame(self::PRODUCT_A, $this->transactions->transactional(
            $organizationId,
            fn(): ?string => $this->repository->findByCode($organizationId, ProductCode::fromString('SKU-001'))?->id()->toString(),
        ));
    }

    public function testSameCodeIsAllowedAcrossTenantsButUniqueWithinOneTenant(): void
    {
        $this->transactions->transactional(
            $this->organizationId(self::ORGANIZATION_A),
            fn() => $this->repository->save($this->product(self::PRODUCT_A, self::ORGANIZATION_A, self::UNIT_A, self::CATEGORY_A)),
        );
        $this->entityManager->clear();
        $this->transactions->transactional(
            $this->organizationId(self::ORGANIZATION_B),
            fn() => $this->repository->save($this->product(self::PRODUCT_B, self::ORGANIZATION_B, self::UNIT_B, self::CATEGORY_B)),
        );
        $this->entityManager->clear();

        $this->expectException(UniqueConstraintViolationException::class);
        $this->transactions->transactional(
            $this->organizationId(self::ORGANIZATION_A),
            fn() => $this->repository->save($this->product(self::PRODUCT_A_DUPLICATE, self::ORGANIZATION_A, self::UNIT_A, self::CATEGORY_A)),
        );
    }

    public function testRlsHidesAProductFromAnotherTenant(): void
    {
        $product = $this->product(self::PRODUCT_B, self::ORGANIZATION_B, self::UNIT_B, self::CATEGORY_B);
        $this->transactions->transactional($product->organizationId(), fn() => $this->repository->save($product));
        $this->entityManager->clear();

        $result = $this->transactions->transactional(
            $this->organizationId(self::ORGANIZATION_A),
            fn(): ?Product => $this->repository->find($this->organizationId(self::ORGANIZATION_A), $product->id()),
        );

        self::assertNull($result);
    }

    public function testDatabaseRejectsABaseUnitFromAnotherTenant(): void
    {
        $this->expectException(ForeignKeyConstraintViolationException::class);
        $this->insertRawProduct(self::PRODUCT_A, self::ORGANIZATION_A, self::UNIT_B, self::CATEGORY_A);
    }

    public function testDatabaseRejectsACategoryFromAnotherTenant(): void
    {
        $this->expectException(ForeignKeyConstraintViolationException::class);
        $this->insertRawProduct(self::PRODUCT_A, self::ORGANIZATION_A, self::UNIT_A, self::CATEGORY_B);
    }

    public function testRepositoryRejectsAStaleProduct(): void
    {
        $product = $this->product(self::PRODUCT_A, self::ORGANIZATION_A, self::UNIT_A, self::CATEGORY_A);
        $this->transactions->transactional($product->organizationId(), fn() => $this->repository->save($product));
        $this->entityManager->clear();
        $stale = $this->transactions->transactional(
            $product->organizationId(),
            fn(): Product => $this->repository->get($product->organizationId(), $product->id()),
        );
        $this->entityManager->clear();
        $this->entityManager->getConnection()->executeStatement(
            'UPDATE catalog.products SET version = version + 1 WHERE id = ?',
            [self::PRODUCT_A],
        );
        $stale->updateProfile(
            $stale->productCode(),
            ProductName::fromString('Café moulu premium'),
            $stale->description(),
            $stale->type(),
            $stale->baseUnitId(),
            $stale->inventoryTracked(),
            $stale->taxCategoryId(),
            $stale->categoryId(),
            $this->actorId(),
            new DateTimeImmutable('2026-08-25T13:00:00Z'),
        );

        $this->expectException(OptimisticLockException::class);
        $this->transactions->transactional($stale->organizationId(), fn() => $this->repository->save($stale));
    }

    private function product(string $id, string $organizationId, string $unitId, string $categoryId): Product
    {
        $factory = new SymfonyUuidFactory();

        return Product::createDraft(
            ProductId::fromString($id, $factory),
            OrganizationId::fromString($organizationId, $factory),
            ProductCode::fromString('sku-001'),
            ProductName::fromString('Café moulu'),
            'Paquet de café',
            ProductType::Physical,
            UnitOfMeasureId::fromString($unitId, $factory),
            true,
            TaxCategoryId::fromString(self::TAX_CATEGORY, $factory),
            CategoryId::fromString($categoryId, $factory),
            $this->actorId(),
            new DateTimeImmutable('2026-08-25T12:00:00Z'),
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

    private function insertUnit(string $id, string $organizationId, string $code): void
    {
        $this->entityManager->getConnection()->executeStatement(<<<'SQL'
INSERT INTO catalog.units_of_measure (
    id, organization_id, code, name, dimension, precision, rounding_mode, status, version
) VALUES (?, ?, ?, 'Article', 'COUNT', 0, 'HalfUp', 'ACTIVE', 1)
SQL, [$id, $organizationId, $code]);
    }

    private function insertCategory(string $id, string $organizationId, string $name): void
    {
        $this->entityManager->getConnection()->executeStatement(<<<'SQL'
INSERT INTO catalog.categories (
    id, organization_id, name, status, created_at, created_by, version
) VALUES (?, ?, ?, 'ACTIVE', NOW(), ?, 1)
SQL, [$id, $organizationId, $name, self::ACTOR_ID]);
    }

    private function insertRawProduct(string $id, string $organizationId, string $unitId, string $categoryId): void
    {
        $this->entityManager->getConnection()->executeStatement(<<<'SQL'
INSERT INTO catalog.products (
    id, organization_id, product_code, name, status, type, base_unit_id,
    inventory_tracked, category_id, created_at, created_by, version
) VALUES (?, ?, 'INVALID', 'Invalid product', 'DRAFT', 'PHYSICAL', ?, TRUE, ?, NOW(), ?, 1)
SQL, [$id, $organizationId, $unitId, $categoryId, self::ACTOR_ID]);
    }

    private function deleteFixtures(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement(
            'DELETE FROM catalog.products WHERE organization_id IN (?, ?)',
            [self::ORGANIZATION_A, self::ORGANIZATION_B],
        );
        $connection->executeStatement(
            'DELETE FROM catalog.categories WHERE organization_id IN (?, ?)',
            [self::ORGANIZATION_A, self::ORGANIZATION_B],
        );
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
