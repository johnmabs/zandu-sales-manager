<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Infrastructure\Persistence\Orm;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Zandu\Modules\Catalog\Domain\Product\Product;
use Zandu\Modules\Catalog\Domain\Product\ProductCode;
use Zandu\Modules\Catalog\Domain\Product\ProductName;
use Zandu\Modules\Catalog\Domain\Product\ProductNotFound;
use Zandu\Modules\Catalog\Domain\Product\ProductRepository;
use Zandu\Modules\Catalog\Domain\Product\ProductStatus;
use Zandu\Modules\Catalog\Domain\Product\ProductType;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\CategoryId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\TaxCategoryId;
use Zandu\SharedKernel\Identity\UnitOfMeasureId;
use Zandu\SharedKernel\Identity\UuidFactory;

final readonly class DoctrineProductRepository implements ProductRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UuidFactory $uuidFactory,
    ) {}

    public function save(Product $product): void
    {
        $record = $this->entityManager->find(ProductRecord::class, $product->id()->toString());

        if ($record instanceof ProductRecord) {
            $expectedPersistedVersion = $product->version() - 1;
            if ($record->version() !== $expectedPersistedVersion) {
                throw OptimisticLockException::lockFailedVersionMismatch(
                    $record,
                    $expectedPersistedVersion,
                    $record->version(),
                );
            }
            $record->synchronize($product);
        } else {
            $this->entityManager->persist(ProductRecord::fromAggregate($product));
        }

        $this->entityManager->flush();
    }

    public function get(OrganizationId $organizationId, ProductId $productId): Product
    {
        return $this->find($organizationId, $productId) ?? throw ProductNotFound::withId($productId);
    }

    public function find(OrganizationId $organizationId, ProductId $productId): ?Product
    {
        $record = $this->entityManager->getRepository(ProductRecord::class)->findOneBy([
            'id' => $productId->toString(),
            'organizationId' => $organizationId->toString(),
        ]);

        return $record instanceof ProductRecord ? $this->toAggregate($record) : null;
    }

    public function findByCode(OrganizationId $organizationId, ProductCode $productCode): ?Product
    {
        $record = $this->entityManager->getRepository(ProductRecord::class)->findOneBy([
            'organizationId' => $organizationId->toString(),
            'productCode' => $productCode->value(),
        ]);

        return $record instanceof ProductRecord ? $this->toAggregate($record) : null;
    }

    public function findAll(
        OrganizationId $organizationId,
        ?ProductStatus $status = null,
        ?ProductType $type = null,
        ?CategoryId $categoryId = null,
        ?ProductCode $productCode = null,
        ?string $search = null,
    ): array {
        $criteria = ['organizationId' => $organizationId->toString()];
        if (null !== $status) {
            $criteria['status'] = $status->value;
        }
        if (null !== $type) {
            $criteria['type'] = $type->value;
        }
        if (null !== $categoryId) {
            $criteria['categoryId'] = $categoryId->toString();
        }
        if (null !== $productCode) {
            $criteria['productCode'] = $productCode->value();
        }
        $records = $this->entityManager->getRepository(ProductRecord::class)->findBy($criteria, ['productCode' => 'ASC']);
        if (null !== $search) {
            $needle = mb_strtolower(trim($search));
            $records = array_filter($records, static fn(ProductRecord $record): bool => str_contains(mb_strtolower($record->name()), $needle) || str_contains(mb_strtolower($record->productCode()), $needle));
        }

        return array_values(array_map(fn(ProductRecord $record): Product => $this->toAggregate($record), $records));
    }

    private function toAggregate(ProductRecord $record): Product
    {
        return Product::reconstitute(
            ProductId::fromString($record->id(), $this->uuidFactory),
            OrganizationId::fromString($record->organizationId(), $this->uuidFactory),
            ProductCode::fromString($record->productCode()),
            ProductName::fromString($record->name()),
            $record->description(),
            ProductStatus::from($record->status()),
            ProductType::from($record->type()),
            UnitOfMeasureId::fromString($record->baseUnitId(), $this->uuidFactory),
            $record->inventoryTracked(),
            null !== $record->taxCategoryId()
                ? TaxCategoryId::fromString($record->taxCategoryId(), $this->uuidFactory)
                : null,
            null !== $record->categoryId()
                ? CategoryId::fromString($record->categoryId(), $this->uuidFactory)
                : null,
            $record->createdAt(),
            ActorId::fromString($record->createdBy(), $this->uuidFactory),
            $record->activatedAt(),
            null !== $record->activatedBy()
                ? ActorId::fromString($record->activatedBy(), $this->uuidFactory)
                : null,
            $record->updatedAt(),
            null !== $record->updatedBy()
                ? ActorId::fromString($record->updatedBy(), $this->uuidFactory)
                : null,
            $record->version(),
        );
    }
}
