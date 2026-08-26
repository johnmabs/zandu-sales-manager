<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Infrastructure\Persistence\Orm;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Zandu\Modules\Pricing\Domain\ProductPrice\ProductPrice;
use Zandu\Modules\Pricing\Domain\ProductPrice\ProductPriceNotFound;
use Zandu\Modules\Pricing\Domain\ProductPrice\ProductPriceRepository;
use Zandu\Modules\Pricing\Domain\ProductPrice\ProductPriceStatus;
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\PriceListId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;
use Zandu\SharedKernel\Identity\ProductPriceId;
use Zandu\SharedKernel\Identity\UuidFactory;
use Zandu\SharedKernel\Money\Currency;
use Zandu\SharedKernel\Money\Money;

final readonly class DoctrineProductPriceRepository implements ProductPriceRepository
{
    public function __construct(
        private EntityManagerInterface $em,
        private UuidFactory $uuids,
        private DecimalFactory $decimals,
    ) {}

    public function save(ProductPrice $productPrice): void
    {
        $record = $this->em->find(ProductPriceRecord::class, $productPrice->id()->toString());
        if ($record instanceof ProductPriceRecord) {
            $expected = $productPrice->version() - 1;
            if ($record->version() !== $expected) {
                throw OptimisticLockException::lockFailedVersionMismatch($record, $expected, $record->version());
            }
            $record->synchronize($productPrice);
        } else {
            $this->em->persist(ProductPriceRecord::fromAggregate($productPrice));
        }
        $this->em->flush();
    }

    public function get(OrganizationId $organizationId, ProductPriceId $id): ProductPrice
    {
        return $this->find($organizationId, $id) ?? throw ProductPriceNotFound::withId($id);
    }

    public function find(OrganizationId $organizationId, ProductPriceId $id): ?ProductPrice
    {
        $value = $this->em->getRepository(ProductPriceRecord::class)->findOneBy([
            'id' => $id->toString(),
            'organizationId' => $organizationId->toString(),
        ]);

        return $this->aggregate($value);
    }

    public function findEffective(
        OrganizationId $organizationId,
        ProductId $productId,
        ProductPackagingId $packagingId,
        DateTimeImmutable $businessInstant,
    ): ?ProductPrice {
        $query = $this->em->createQueryBuilder()
            ->select('productPrice')
            ->from(ProductPriceRecord::class, 'productPrice')
            ->innerJoin(
                PriceListRecord::class,
                'priceList',
                'WITH',
                'priceList.id = productPrice.priceListId AND priceList.organizationId = productPrice.organizationId AND priceList.currency = productPrice.currency',
            )
            ->andWhere('productPrice.organizationId = :organizationId')
            ->andWhere('productPrice.productId = :productId')
            ->andWhere('productPrice.packagingId = :packagingId')
            ->andWhere('productPrice.status = :active')
            ->andWhere('priceList.status = :active')
            ->andWhere('(productPrice.validFrom IS NULL OR productPrice.validFrom <= :businessInstant)')
            ->andWhere('(productPrice.validTo IS NULL OR productPrice.validTo >= :businessInstant)')
            ->andWhere('(priceList.validFrom IS NULL OR priceList.validFrom <= :businessInstant)')
            ->andWhere('(priceList.validTo IS NULL OR priceList.validTo >= :businessInstant)')
            ->setParameter('organizationId', $organizationId->toString())
            ->setParameter('productId', $productId->toString())
            ->setParameter('packagingId', $packagingId->toString())
            ->setParameter('active', ProductPriceStatus::Active->value)
            ->setParameter('businessInstant', $businessInstant)
            ->orderBy('priceList.priority', 'DESC')
            ->addOrderBy('priceList.id', 'ASC')
            ->addOrderBy('productPrice.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $this->aggregate($query);
    }

    public function findAll(OrganizationId $organizationId): array
    {
        return array_values(array_filter(array_map(fn(mixed $record): ?ProductPrice => $this->aggregate($record), $this->em->getRepository(ProductPriceRecord::class)->findBy(['organizationId' => $organizationId->toString()], ['id' => 'ASC']))));
    }

    private function aggregate(mixed $value): ?ProductPrice
    {
        if (!$value instanceof ProductPriceRecord) {
            return null;
        }

        return ProductPrice::reconstitute(
            ProductPriceId::fromString($value->id(), $this->uuids),
            OrganizationId::fromString($value->organizationId(), $this->uuids),
            PriceListId::fromString($value->priceListId(), $this->uuids),
            ProductId::fromString($value->productId(), $this->uuids),
            ProductPackagingId::fromString($value->packagingId(), $this->uuids),
            Money::fromString($value->amount(), Currency::fromCode($value->currency()), $this->decimals),
            ProductPriceStatus::from($value->status()),
            $value->validFrom(),
            $value->validTo(),
            $value->createdAt(),
            ActorId::fromString($value->createdBy(), $this->uuids),
            $value->version(),
        );
    }
}
