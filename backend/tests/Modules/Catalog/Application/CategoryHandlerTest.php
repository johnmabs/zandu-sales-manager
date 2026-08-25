<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Catalog\Application;

use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Catalog\Application\ActivateCategory\ActivateCategory;
use Zandu\Modules\Catalog\Application\ActivateCategory\ActivateCategoryHandler;
use Zandu\Modules\Catalog\Application\ArchiveCategory\ArchiveCategory;
use Zandu\Modules\Catalog\Application\ArchiveCategory\ArchiveCategoryHandler;
use Zandu\Modules\Catalog\Application\CreateCategory\CreateCategory;
use Zandu\Modules\Catalog\Application\CreateCategory\CreateCategoryHandler;
use Zandu\Modules\Catalog\Application\DeactivateCategory\DeactivateCategory;
use Zandu\Modules\Catalog\Application\DeactivateCategory\DeactivateCategoryHandler;
use Zandu\Modules\Catalog\Application\MoveCategory\MoveCategory;
use Zandu\Modules\Catalog\Application\MoveCategory\MoveCategoryHandler;
use Zandu\Modules\Catalog\Application\TenantCategoryLoader;
use Zandu\Modules\Catalog\Application\UpdateCategory\UpdateCategory;
use Zandu\Modules\Catalog\Application\UpdateCategory\UpdateCategoryHandler;
use Zandu\Modules\Catalog\Domain\Category\Category;
use Zandu\Modules\Catalog\Domain\Category\CategoryName;
use Zandu\Modules\Catalog\Domain\Category\CategoryNotFound;
use Zandu\Modules\Catalog\Domain\Category\CategoryRepository;
use Zandu\Modules\Catalog\Domain\Category\CategoryStatus;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Modules\Organization\Application\Contract\OperationalMode;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Context\ActorType;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\CategoryId;
use Zandu\SharedKernel\Identity\IdGenerator;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Identity\Uuid;
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\Tests\SharedKernel\Time\FrozenClock;

final class CategoryHandlerTest extends TestCase
{
    private const ORGANIZATION_ID = '0198d1b1-b2a4-7b6e-8e0e-608484906502';
    private const OTHER_ORGANIZATION_ID = '0198d1b1-b2a4-7b6e-8e0e-608484906503';
    private const CATEGORY_ID = '0198d2a1-147c-72d5-b75a-a936797ff9c8';
    private const PARENT_ID = '0198d2a2-147c-72d5-b75a-a936797ff9c8';
    private const CHILD_ID = '0198d2a3-147c-72d5-b75a-a936797ff9c8';
    private const ACTOR_ID = '0198c728-8f2d-7f43-92d8-3f0c75b80186';
    private const CORRELATION_ID = '0198c729-19da-75be-b508-1a4b36cf8d7a';

    private CategoryUseCaseRepository $categories;
    private CategoryTenantTransaction $transaction;
    private FrozenClock $clock;
    private RecordingCategoryAuthorization $authorization;
    private RecordingCategoryOperationalGuard $operationalGuard;

    protected function setUp(): void
    {
        $this->categories = new CategoryUseCaseRepository();
        $this->transaction = new CategoryTenantTransaction();
        $this->clock = new FrozenClock(new DateTimeImmutable('2026-08-25T23:00:00Z'));
        $this->authorization = new RecordingCategoryAuthorization();
        $this->operationalGuard = new RecordingCategoryOperationalGuard();
    }

    public function testItCreatesARootCategoryInsideALockedTenantTransaction(): void
    {
        $category = $this->createHandler()(new CreateCategory(' Boissons ', null, $this->context()));

        self::assertSame(self::CATEGORY_ID, $category->id()->toString());
        self::assertSame('Boissons', $category->name()->value());
        self::assertNull($category->parentCategoryId());
        self::assertSame(self::ORGANIZATION_ID, $this->transaction->lastOrganizationId?->toString());
        self::assertSame(self::ORGANIZATION_ID, $this->categories->lastLockedOrganizationId?->toString());
        self::assertSame([PermissionCode::CategoryCreate], $this->authorization->permissions);
        self::assertSame(1, $this->operationalGuard->tenantChecks);
    }

    public function testItCreatesAChildOnlyFromAParentVisibleToTheTenant(): void
    {
        $parent = $this->category(self::PARENT_ID, 'Alimentation');
        $this->categories->save($parent);
        $child = $this->createHandler()(new CreateCategory('Boissons', $parent->id(), $this->context()));

        self::assertTrue($child->parentCategoryId()?->equals($parent->id()));

        $this->expectException(CategoryNotFound::class);
        $this->createHandler()(new CreateCategory('Interdit', $parent->id(), $this->context(self::OTHER_ORGANIZATION_ID)));
    }

    public function testUpdateAndLifecycleAreDedicatedUseCases(): void
    {
        $category = $this->createHandler()(new CreateCategory('Boissons', null, $this->context()));
        $loader = new TenantCategoryLoader($this->categories);

        $update = new UpdateCategoryHandler($loader, $this->categories, $this->clock, $this->transaction, $this->authorization, $this->operationalGuard);
        $category = $update(new UpdateCategory($category->id(), 'Boissons fraîches', $this->context()));
        self::assertSame('Boissons fraîches', $category->name()->value());

        $deactivate = new DeactivateCategoryHandler($loader, $this->categories, $this->clock, $this->transaction, $this->authorization, $this->operationalGuard);
        $category = $deactivate(new DeactivateCategory($category->id(), $this->context()));
        self::assertSame(CategoryStatus::Inactive, $category->status());

        $activate = new ActivateCategoryHandler($loader, $this->categories, $this->clock, $this->transaction, $this->authorization, $this->operationalGuard);
        $category = $activate(new ActivateCategory($category->id(), $this->context()));
        self::assertSame(CategoryStatus::Active, $category->status());

        $archive = new ArchiveCategoryHandler($loader, $this->categories, $this->clock, $this->transaction, $this->authorization, $this->operationalGuard);
        $category = $archive(new ArchiveCategory($category->id(), $this->context()));
        self::assertSame(CategoryStatus::Archived, $category->status());
        self::assertSame(5, $category->version());
        self::assertSame([
            PermissionCode::CategoryCreate,
            PermissionCode::CategoryUpdate,
            PermissionCode::CategoryUpdate,
            PermissionCode::CategoryUpdate,
            PermissionCode::CategoryArchive,
        ], $this->authorization->permissions);
        self::assertSame(5, $this->operationalGuard->tenantChecks);
    }

    public function testMoveToRootAndToAnotherBranchAreExplicit(): void
    {
        $firstParent = $this->category(self::PARENT_ID, 'Alimentation');
        $secondParent = $this->category(self::CHILD_ID, 'Promotions');
        $category = $this->category(self::CATEGORY_ID, 'Boissons', $firstParent);
        $this->categories->save($firstParent);
        $this->categories->save($secondParent);
        $this->categories->save($category);
        $handler = new MoveCategoryHandler(
            new TenantCategoryLoader($this->categories),
            $this->categories,
            $this->clock,
            $this->transaction,
            $this->authorization,
            $this->operationalGuard,
        );

        $category = $handler(new MoveCategory($category->id(), null, $this->context()));
        self::assertNull($category->parentCategoryId());

        $category = $handler(new MoveCategory($category->id(), $secondParent->id(), $this->context()));
        self::assertTrue($category->parentCategoryId()?->equals($secondParent->id()));
        self::assertSame(self::ORGANIZATION_ID, $this->categories->lastLockedOrganizationId?->toString());
        self::assertSame([PermissionCode::CategoryUpdate, PermissionCode::CategoryUpdate], $this->authorization->permissions);
        self::assertSame(2, $this->operationalGuard->tenantChecks);
    }

    public function testMoveRejectsDescendantAsParentUsingRepositoryAncestors(): void
    {
        $category = $this->category(self::CATEGORY_ID, 'Root');
        $child = $this->category(self::CHILD_ID, 'Child', $category);
        $this->categories->save($category);
        $this->categories->save($child);
        $handler = new MoveCategoryHandler(
            new TenantCategoryLoader($this->categories),
            $this->categories,
            $this->clock,
            $this->transaction,
            $this->authorization,
            $this->operationalGuard,
        );

        $this->expectException(LogicException::class);
        $handler(new MoveCategory($category->id(), $child->id(), $this->context()));
    }

    private function createHandler(): CreateCategoryHandler
    {
        $uuid = (new SymfonyUuidFactory())->fromString(self::CATEGORY_ID);

        return new CreateCategoryHandler(
            $this->categories,
            new class ($uuid) implements IdGenerator {
                public function __construct(private readonly Uuid $uuid) {}
                public function generate(): Uuid
                {
                    return $this->uuid;
                }
            },
            $this->clock,
            $this->transaction,
            $this->authorization,
            $this->operationalGuard,
        );
    }

    private function category(string $id, string $name, ?Category $parent = null): Category
    {
        return Category::create(
            $this->categoryId($id),
            $this->organizationId(),
            CategoryName::fromString($name),
            $parent,
            $this->actorId(),
            new DateTimeImmutable('2026-08-25T22:00:00Z'),
        );
    }

    private function context(string $organizationId = self::ORGANIZATION_ID): ActorContext
    {
        $factory = new SymfonyUuidFactory();

        return new ActorContext(
            $this->actorId(),
            $this->organizationId($organizationId),
            ActorType::User,
            CorrelationId::fromString(self::CORRELATION_ID, $factory),
            new DateTimeImmutable('2026-08-25T21:00:00Z'),
        );
    }

    private function categoryId(string $id): CategoryId
    {
        return CategoryId::fromString($id, new SymfonyUuidFactory());
    }

    private function organizationId(string $id = self::ORGANIZATION_ID): OrganizationId
    {
        return OrganizationId::fromString($id, new SymfonyUuidFactory());
    }

    private function actorId(): ActorId
    {
        return ActorId::fromString(self::ACTOR_ID, new SymfonyUuidFactory());
    }
}

final class RecordingCategoryAuthorization implements AuthorizationService
{
    /** @var list<PermissionCode> */
    public array $permissions = [];

    public function authorize(ActorContext $actorContext, PermissionCode $permission, ResourceScope $resourceScope): void
    {
        $this->permissions[] = $permission;
        TestCase::assertTrue($actorContext->organizationId()->equals($resourceScope->organizationId));
        TestCase::assertNull($resourceScope->storeId);
    }
}

final class RecordingCategoryOperationalGuard implements OperationalGuard
{
    public int $tenantChecks = 0;

    public function assertTenant(ActorContext $actorContext, OperationalMode $mode = OperationalMode::Standard): void
    {
        ++$this->tenantChecks;
        TestCase::assertSame(OperationalMode::Standard, $mode);
    }

    public function assertStore(ActorContext $actorContext, StoreId $storeId, OperationalMode $mode = OperationalMode::Standard): void
    {
        TestCase::fail('Category mutations must not use a store-scoped operational guard.');
    }
}

final class CategoryUseCaseRepository implements CategoryRepository
{
    /** @var array<string, Category> */
    private array $categories = [];
    public ?OrganizationId $lastLockedOrganizationId = null;

    public function lockHierarchy(OrganizationId $organizationId): void
    {
        $this->lastLockedOrganizationId = $organizationId;
    }

    public function save(Category $category): void
    {
        $this->categories[$category->id()->toString()] = $category;
    }

    public function get(OrganizationId $organizationId, CategoryId $categoryId): Category
    {
        return $this->find($organizationId, $categoryId) ?? throw CategoryNotFound::withId($categoryId);
    }

    public function find(OrganizationId $organizationId, CategoryId $categoryId): ?Category
    {
        $category = $this->categories[$categoryId->toString()] ?? null;

        return $category instanceof Category && $category->organizationId()->equals($organizationId) ? $category : null;
    }

    public function findAll(OrganizationId $organizationId): array
    {
        return array_values(array_filter(
            $this->categories,
            static fn(Category $category): bool => $category->organizationId()->equals($organizationId),
        ));
    }

    public function findAncestors(OrganizationId $organizationId, CategoryId $categoryId): array
    {
        $category = $this->get($organizationId, $categoryId);
        $ancestors = [];
        $visited = [$categoryId->toString() => true];

        while (null !== $category->parentCategoryId()) {
            $parentId = $category->parentCategoryId();
            if (isset($visited[$parentId->toString()])) {
                throw new LogicException('Persisted category hierarchy contains a cycle.');
            }
            $visited[$parentId->toString()] = true;
            $category = $this->get($organizationId, $parentId);
            $ancestors[] = $category;
        }

        return $ancestors;
    }
}

final class CategoryTenantTransaction implements TenantTransaction
{
    public ?OrganizationId $lastOrganizationId = null;

    public function transactional(OrganizationId $organizationId, callable $operation): mixed
    {
        $this->lastOrganizationId = $organizationId;

        return $operation();
    }
}
