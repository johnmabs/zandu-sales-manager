<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Infrastructure\Persistence\Orm;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Zandu\Modules\Catalog\Application\Contract\BasePackagingPresence;
use Zandu\Modules\Catalog\Domain\ProductPackaging\ConversionFactor;
use Zandu\Modules\Catalog\Domain\ProductPackaging\ProductPackaging;
use Zandu\Modules\Catalog\Domain\ProductPackaging\ProductPackagingCode;
use Zandu\Modules\Catalog\Domain\ProductPackaging\ProductPackagingName;
use Zandu\Modules\Catalog\Domain\ProductPackaging\ProductPackagingNotFound;
use Zandu\Modules\Catalog\Domain\ProductPackaging\ProductPackagingPrecision;
use Zandu\Modules\Catalog\Domain\ProductPackaging\ProductPackagingRepository;
use Zandu\Modules\Catalog\Domain\ProductPackaging\ProductPackagingStatus;
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;
use Zandu\SharedKernel\Identity\UnitOfMeasureId;
use Zandu\SharedKernel\Identity\UuidFactory;
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class DoctrineProductPackagingRepository implements ProductPackagingRepository, BasePackagingPresence
{
    public function __construct(private EntityManagerInterface $em, private UuidFactory $uuids, private DecimalFactory $decimals) {}

    public function save(ProductPackaging $packaging): void
    {
        $record = $this->em->find(ProductPackagingRecord::class, $packaging->id()->toString());
        if ($record instanceof ProductPackagingRecord) {
            $expected = $packaging->version() - 1;
            if ($record->version() !== $expected) {
                throw OptimisticLockException::lockFailedVersionMismatch($record, $expected, $record->version());
            }
            $record->synchronize($packaging);
        } else {
            $this->em->persist(ProductPackagingRecord::fromAggregate($packaging));
        }
        $this->em->flush();
    }

    public function get(OrganizationId $organizationId, ProductPackagingId $id): ProductPackaging
    {
        return $this->find($organizationId, $id) ?? throw ProductPackagingNotFound::withId($id);
    }

    public function find(OrganizationId $organizationId, ProductPackagingId $id): ?ProductPackaging
    {
        return $this->aggregate($this->em->getRepository(ProductPackagingRecord::class)->findOneBy(['id' => $id->toString(), 'organizationId' => $organizationId->toString()]));
    }

    public function findByCode(OrganizationId $organizationId, ProductId $productId, ProductPackagingCode $code): ?ProductPackaging
    {
        return $this->aggregate($this->em->getRepository(ProductPackagingRecord::class)->findOneBy(['organizationId' => $organizationId->toString(), 'productId' => $productId->toString(), 'code' => $code->value()]));
    }

    public function findBase(OrganizationId $organizationId, ProductId $productId): ?ProductPackaging
    {
        return $this->aggregate($this->em->getRepository(ProductPackagingRecord::class)->findOneBy(['organizationId' => $organizationId->toString(), 'productId' => $productId->toString(), 'base' => true]));
    }

    public function exists(OrganizationId $organizationId, ProductId $productId, UnitOfMeasureId $baseUnitId): bool
    {
        $base = $this->findBase($organizationId, $productId);
        return null !== $base && $base->unitId()->equals($baseUnitId) && $base->conversionFactor()->isOne() && ProductPackagingStatus::Archived !== $base->status();
    }

    private function aggregate(mixed $value): ?ProductPackaging
    {
        if (!$value instanceof ProductPackagingRecord) {
            return null;
        }
        return ProductPackaging::reconstitute(
            ProductPackagingId::fromString($value->id(), $this->uuids),
            OrganizationId::fromString($value->organizationId(), $this->uuids),
            ProductId::fromString($value->productId(), $this->uuids),
            $value->base(),
            ProductPackagingCode::fromString($value->code()),
            ProductPackagingName::fromString($value->name()),
            UnitOfMeasureId::fromString($value->unitId(), $this->uuids),
            new ConversionFactor($this->decimals->fromString($value->conversionFactor())),
            ProductPackagingPrecision::fromInt($value->precision()),
            Quantity::fromString($value->minimumQuantity(), $this->decimals),
            Quantity::fromString($value->quantityIncrement(), $this->decimals),
            $value->allowedForSale(),
            $value->allowedForPurchase(),
            ProductPackagingStatus::from($value->status()),
            $value->createdAt(),
            ActorId::fromString($value->createdBy(), $this->uuids),
            $value->updatedAt(),
            null !== $value->updatedBy() ? ActorId::fromString($value->updatedBy(), $this->uuids) : null,
            $value->version(),
        );
    }
}
