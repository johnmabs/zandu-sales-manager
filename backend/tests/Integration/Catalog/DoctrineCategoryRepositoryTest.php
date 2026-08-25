<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\Catalog;

use DateTimeImmutable;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;
use Zandu\Modules\Catalog\Domain\Category\Category;
use Zandu\Modules\Catalog\Domain\Category\CategoryName;
use Zandu\Modules\Catalog\Domain\Category\CategoryRepository;
use Zandu\Modules\Catalog\Domain\Category\CategoryStatus;
use Zandu\Modules\Catalog\Infrastructure\Persistence\Orm\DoctrineCategoryRepository;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\Platform\Persistence\DoctrineTenantTransaction;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\CategoryId;
use Zandu\SharedKernel\Identity\OrganizationId;

final class DoctrineCategoryRepositoryTest extends KernelTestCase
{
    private const ORGANIZATION_A = '0198d291-ab09-73bf-b631-c307fd6ed08d';
    private const ORGANIZATION_B = '0198d292-70a2-71df-8beb-b7ae882c8dba';
    private const ROOT = '0198d293-147c-72d5-b75a-a936797ff9c8';
    private const PARENT = '0198d294-537f-75b8-bd7f-550f88270881';
    private const CHILD = '0198d295-3765-7eb3-8ef9-f3661c32bc08';
    private const OTHER_TENANT_PARENT = '0198d296-3765-7eb3-8ef9-f3661c32bc08';
    private const ACTOR_ID = '0198d1b2-1dd7-7c6d-9855-25e5e205940c';

    private EntityManagerInterface $entityManager;
    private CategoryRepository $repository;
    private DoctrineTenantTransaction $transactions;
    private bool $databaseAvailable = false;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->repository = new DoctrineCategoryRepository($this->entityManager, new SymfonyUuidFactory());
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
        $this->insertOrganization(self::ORGANIZATION_A, 'Category tenant A');
        $this->insertOrganization(self::ORGANIZATION_B, 'Category tenant B');
    }

    protected function tearDown(): void
    {
        if ($this->databaseAvailable) {
            $this->entityManager->clear();
            $this->deleteFixtures();
        }

        parent::tearDown();
    }

    public function testCategoryHierarchyRoundTripsAndAncestorsAreOrderedParentFirst(): void
    {
        $root = $this->category(self::ROOT, self::ORGANIZATION_A, 'Alimentation');
        $parent = $this->category(self::PARENT, self::ORGANIZATION_A, 'Boissons', $root);
        $child = $this->category(self::CHILD, self::ORGANIZATION_A, 'Boissons chaudes', $parent);
        $organizationId = $this->organizationId(self::ORGANIZATION_A);

        $this->transactions->transactional($organizationId, function () use ($root, $parent, $child): void {
            $this->repository->save($root);
            $this->repository->save($parent);
            $this->repository->save($child);
        });
        $this->entityManager->clear();

        $restored = $this->transactions->transactional(
            $organizationId,
            fn(): Category => $this->repository->get($organizationId, $child->id()),
        );
        $ancestors = $this->transactions->transactional(
            $organizationId,
            fn(): array => $this->repository->findAncestors($organizationId, $child->id()),
        );

        self::assertTrue($restored->parentCategoryId()?->equals($parent->id()));
        self::assertSame(['Boissons', 'Alimentation'], array_map(
            static fn(Category $category): string => $category->name()->value(),
            $ancestors,
        ));
    }

    public function testHierarchyLockCanBeAcquiredInsideTenantTransaction(): void
    {
        $organizationId = $this->organizationId(self::ORGANIZATION_A);

        $acquired = $this->transactions->transactional($organizationId, function () use ($organizationId): bool {
            $this->repository->lockHierarchy($organizationId);

            return true;
        });

        self::assertTrue($acquired);
    }

    public function testLifecycleUpdateRoundTripsWithOptimisticVersion(): void
    {
        $category = $this->category(self::ROOT, self::ORGANIZATION_A, 'Boissons');
        $organizationId = $category->organizationId();

        $restored = $this->transactions->transactional($organizationId, function () use ($category): Category {
            $this->repository->save($category);
            $category->archive($this->actorId(), new DateTimeImmutable('2026-08-25T22:00:00Z'));
            $this->repository->save($category);
            $this->entityManager->clear();

            return $this->repository->get($category->organizationId(), $category->id());
        });

        self::assertSame(CategoryStatus::Archived, $restored->status());
        self::assertSame(2, $restored->version());
        self::assertSame(self::ACTOR_ID, $restored->updatedBy()?->toString());
    }

    public function testRlsHidesCategoryFromAnotherTenant(): void
    {
        $category = $this->category(self::OTHER_TENANT_PARENT, self::ORGANIZATION_B, 'Tenant B');
        $this->transactions->transactional(
            $category->organizationId(),
            fn() => $this->repository->save($category),
        );
        $this->entityManager->clear();

        $result = $this->transactions->transactional(
            $this->organizationId(self::ORGANIZATION_A),
            fn(): ?Category => $this->repository->find($category->organizationId(), $category->id()),
        );

        self::assertNull($result);
    }

    public function testDatabaseRejectsCrossTenantParentRelationship(): void
    {
        $otherParent = $this->category(self::OTHER_TENANT_PARENT, self::ORGANIZATION_B, 'Tenant B parent');
        $this->transactions->transactional(
            $otherParent->organizationId(),
            fn() => $this->repository->save($otherParent),
        );

        $this->expectException(ForeignKeyConstraintViolationException::class);
        $this->entityManager->getConnection()->executeStatement(<<<'SQL'
INSERT INTO catalog.categories (
    id, organization_id, name, parent_category_id, status,
    created_at, created_by, version
) VALUES (?, ?, 'Invalid child', ?, 'ACTIVE', NOW(), ?, 1)
SQL, [self::CHILD, self::ORGANIZATION_A, self::OTHER_TENANT_PARENT, self::ACTOR_ID]);
    }

    public function testRepositoryRejectsAStaleCategory(): void
    {
        $category = $this->category(self::ROOT, self::ORGANIZATION_A, 'Boissons');
        $this->transactions->transactional(
            $category->organizationId(),
            fn() => $this->repository->save($category),
        );
        $this->entityManager->clear();
        $stale = $this->transactions->transactional(
            $category->organizationId(),
            fn(): Category => $this->repository->get($category->organizationId(), $category->id()),
        );
        $this->entityManager->clear();
        $this->entityManager->getConnection()->executeStatement(
            'UPDATE catalog.categories SET version = version + 1 WHERE id = ?',
            [self::ROOT],
        );
        $stale->deactivate($this->actorId(), new DateTimeImmutable('2026-08-25T23:00:00Z'));

        $this->expectException(OptimisticLockException::class);
        $this->transactions->transactional(
            $stale->organizationId(),
            fn() => $this->repository->save($stale),
        );
    }

    public function testAncestorTraversalFailsFastOnPersistedCycle(): void
    {
        $root = $this->category(self::ROOT, self::ORGANIZATION_A, 'Root');
        $child = $this->category(self::CHILD, self::ORGANIZATION_A, 'Child', $root);
        $organizationId = $root->organizationId();
        $this->transactions->transactional($organizationId, function () use ($root, $child): void {
            $this->repository->save($root);
            $this->repository->save($child);
        });
        $this->entityManager->clear();
        $this->entityManager->getConnection()->executeStatement(
            'UPDATE catalog.categories SET parent_category_id = ? WHERE id = ?',
            [self::CHILD, self::ROOT],
        );

        $this->expectException(LogicException::class);
        $this->transactions->transactional(
            $organizationId,
            fn(): array => $this->repository->findAncestors($organizationId, $child->id()),
        );
    }

    private function category(
        string $id,
        string $organizationId,
        string $name,
        ?Category $parent = null,
    ): Category {
        return Category::create(
            $this->categoryId($id),
            $this->organizationId($organizationId),
            CategoryName::fromString($name),
            $parent,
            $this->actorId(),
            new DateTimeImmutable('2026-08-25T20:00:00Z'),
        );
    }

    private function categoryId(string $id): CategoryId
    {
        return CategoryId::fromString($id, new SymfonyUuidFactory());
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
            'DELETE FROM catalog.categories WHERE organization_id IN (?, ?)',
            [self::ORGANIZATION_A, self::ORGANIZATION_B],
        );
        $connection->executeStatement(
            'DELETE FROM organization.organizations WHERE id IN (?, ?)',
            [self::ORGANIZATION_A, self::ORGANIZATION_B],
        );
    }
}
