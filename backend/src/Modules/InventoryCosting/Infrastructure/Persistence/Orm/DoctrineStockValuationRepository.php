<?php

declare(strict_types=1);

namespace Zandu\Modules\InventoryCosting\Infrastructure\Persistence\Orm;

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Zandu\Modules\InventoryCosting\Domain\InventoryCostingRuleViolation;
use Zandu\Modules\InventoryCosting\Domain\Valuation\{StockValuation, StockValuationRepository};
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\SharedKernel\Identity\{OrganizationId, ProductId, StockId, StockValuationId, StoreId, UuidFactory};
use Zandu\SharedKernel\Money\{Currency, Money};
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class DoctrineStockValuationRepository implements StockValuationRepository
{
    public function __construct(
        private EntityManagerInterface $em,
        private UuidFactory $uuids,
        private BrickDecimalFactory $decimals,
    ) {}

    public function save(StockValuation $valuation): void
    {
        $record = $this->em->find(StockValuationRecord::class, $valuation->id()->toString());
        if ($record instanceof StockValuationRecord) {
            $expectedVersion = $valuation->version() - 1;
            if ($record->version() !== $expectedVersion) {
                throw OptimisticLockException::lockFailedVersionMismatch($record, $expectedVersion, $record->version());
            }
            $record->synchronize($valuation);
        } else {
            $this->em->persist(StockValuationRecord::fromAggregate($valuation));
        }
        $this->em->flush();
    }

    public function findByStock(OrganizationId $organizationId, StockId $stockId): ?StockValuation
    {
        return $this->aggregate($this->em->getRepository(StockValuationRecord::class)->findOneBy([
            'organizationId' => $organizationId->toString(),
            'stockId' => $stockId->toString(),
        ]));
    }

    public function getByStock(OrganizationId $organizationId, StockId $stockId): StockValuation
    {
        return $this->findByStock($organizationId, $stockId) ?? $this->notInitialized();
    }

    public function getByStockForUpdate(OrganizationId $organizationId, StockId $stockId): StockValuation
    {
        $record = $this->em->createQueryBuilder()
            ->select('valuation')
            ->from(StockValuationRecord::class, 'valuation')
            ->where('valuation.organizationId = :organizationId')
            ->andWhere('valuation.stockId = :stockId')
            ->setParameter('organizationId', $organizationId->toString())
            ->setParameter('stockId', $stockId->toString())
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult();

        return $this->aggregate($record) ?? $this->notInitialized();
    }

    public function findByStore(OrganizationId $organizationId, StoreId $storeId): array
    {
        return array_values(array_filter(array_map(
            fn(mixed $record): ?StockValuation => $this->aggregate($record),
            $this->em->getRepository(StockValuationRecord::class)->findBy([
                'organizationId' => $organizationId->toString(),
                'storeId' => $storeId->toString(),
            ], ['productId' => 'ASC']),
        )));
    }

    private function aggregate(mixed $record): ?StockValuation
    {
        if (!$record instanceof StockValuationRecord) {
            return null;
        }

        return StockValuation::reconstitute(
            StockValuationId::fromString($record->id(), $this->uuids),
            OrganizationId::fromString($record->organizationId(), $this->uuids),
            StoreId::fromString($record->storeId(), $this->uuids),
            ProductId::fromString($record->productId(), $this->uuids),
            StockId::fromString($record->stockId(), $this->uuids),
            Quantity::fromString($record->quantityOnHand(), $this->decimals),
            Money::fromString($record->totalValue(), Currency::fromCode($record->currency()), $this->decimals),
            $record->version(),
        );
    }

    private function notInitialized(): never
    {
        throw InventoryCostingRuleViolation::with(
            'VALUATION_NOT_INITIALIZED',
            'Stock valuation must be initialized.',
        );
    }
}
