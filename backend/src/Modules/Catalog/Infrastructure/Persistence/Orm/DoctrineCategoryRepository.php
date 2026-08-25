<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Infrastructure\Persistence\Orm;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use LogicException;
use Zandu\Modules\Catalog\Domain\Category\Category;
use Zandu\Modules\Catalog\Domain\Category\CategoryName;
use Zandu\Modules\Catalog\Domain\Category\CategoryNotFound;
use Zandu\Modules\Catalog\Domain\Category\CategoryRepository;
use Zandu\Modules\Catalog\Domain\Category\CategoryStatus;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\CategoryId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\UuidFactory;

final readonly class DoctrineCategoryRepository implements CategoryRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UuidFactory $uuidFactory,
    ) {}

    public function lockHierarchy(OrganizationId $organizationId): void
    {
        $this->entityManager->getConnection()->fetchOne(
            'SELECT pg_advisory_xact_lock(hashtextextended(?, 0))',
            [$organizationId->toString()],
        );
    }

    public function save(Category $category): void
    {
        $record = $this->entityManager->find(CategoryRecord::class, $category->id()->toString());

        if ($record instanceof CategoryRecord) {
            $expectedPersistedVersion = $category->version() - 1;
            if ($record->version() !== $expectedPersistedVersion) {
                throw OptimisticLockException::lockFailedVersionMismatch(
                    $record,
                    $expectedPersistedVersion,
                    $record->version(),
                );
            }
            $record->synchronize($category);
        } else {
            $this->entityManager->persist(CategoryRecord::fromAggregate($category));
        }

        $this->entityManager->flush();
    }

    public function get(OrganizationId $organizationId, CategoryId $categoryId): Category
    {
        return $this->find($organizationId, $categoryId) ?? throw CategoryNotFound::withId($categoryId);
    }

    public function find(OrganizationId $organizationId, CategoryId $categoryId): ?Category
    {
        $record = $this->entityManager->getRepository(CategoryRecord::class)->findOneBy([
            'id' => $categoryId->toString(),
            'organizationId' => $organizationId->toString(),
        ]);

        return $record instanceof CategoryRecord ? $this->toAggregate($record) : null;
    }

    public function findAll(OrganizationId $organizationId): array
    {
        $records = $this->entityManager->getRepository(CategoryRecord::class)->findBy(
            ['organizationId' => $organizationId->toString()],
            ['name' => 'ASC', 'id' => 'ASC'],
        );

        return array_map($this->toAggregate(...), $records);
    }

    public function findAncestors(OrganizationId $organizationId, CategoryId $categoryId): array
    {
        $category = $this->get($organizationId, $categoryId);
        $ancestors = [];
        $visited = [$categoryId->toString() => true];

        while (null !== $category->parentCategoryId()) {
            $parentId = $category->parentCategoryId();
            $parentKey = $parentId->toString();
            if (isset($visited[$parentKey])) {
                throw new LogicException('Persisted category hierarchy contains a cycle.');
            }
            $visited[$parentKey] = true;
            $category = $this->get($organizationId, $parentId);
            $ancestors[] = $category;
        }

        return $ancestors;
    }

    private function toAggregate(CategoryRecord $record): Category
    {
        return Category::reconstitute(
            CategoryId::fromString($record->id(), $this->uuidFactory),
            OrganizationId::fromString($record->organizationId(), $this->uuidFactory),
            CategoryName::fromString($record->name()),
            null !== $record->parentCategoryId()
                ? CategoryId::fromString($record->parentCategoryId(), $this->uuidFactory)
                : null,
            CategoryStatus::from($record->status()),
            $record->createdAt(),
            ActorId::fromString($record->createdBy(), $this->uuidFactory),
            $record->updatedAt(),
            null !== $record->updatedBy()
                ? ActorId::fromString($record->updatedBy(), $this->uuidFactory)
                : null,
            $record->version(),
        );
    }
}
