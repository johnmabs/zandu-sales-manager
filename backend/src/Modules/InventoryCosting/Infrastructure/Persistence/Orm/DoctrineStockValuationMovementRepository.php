<?php

declare(strict_types=1);

namespace Zandu\Modules\InventoryCosting\Infrastructure\Persistence\Orm;

use Doctrine\ORM\EntityManagerInterface;
use Zandu\Modules\InventoryCosting\Domain\ValuationMovement\{StockValuationMovement, StockValuationMovementRepository, StockValuationMovementSource, StockValuationMovementType};
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\SharedKernel\Identity\{OrganizationId, ProductId, StockId, StockMovementId, StockValuationId, StockValuationMovementId, StoreId, UuidFactory};
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\Money\{Currency, Money};
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class DoctrineStockValuationMovementRepository implements StockValuationMovementRepository
{
    public function __construct(
        private EntityManagerInterface $em,
        private UuidFactory $uuids,
        private BrickDecimalFactory $decimals,
    ) {}

    public function append(StockValuationMovement $movement): void
    {
        $this->em->persist(StockValuationMovementRecord::fromDomain($movement));
        $this->em->flush();
    }

    public function appendOnce(StockValuationMovement $movement): bool
    {
        $affected = $this->em->getConnection()->executeStatement(
            <<<'SQL'
INSERT INTO inventory_costing.stock_valuation_movement
    (id, stock_valuation_id, organization_id, store_id, product_id, stock_id, stock_movement_id,
     type, quantity, unit_cost, value, previous_total_value, resulting_total_value,
     previous_average_cost, resulting_average_cost, currency, source_type, source_reference_id,
     occurred_at, correlation_id)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
ON CONFLICT DO NOTHING
SQL,
            $this->parameters($movement),
        );

        return 1 === $affected;
    }

    public function findByValuation(OrganizationId $organizationId, StockValuationId $valuationId): array
    {
        return array_map(
            fn(StockValuationMovementRecord $record): StockValuationMovement => $this->domain($record),
            $this->em->getRepository(StockValuationMovementRecord::class)->findBy([
                'organizationId' => $organizationId->toString(),
                'stockValuationId' => $valuationId->toString(),
            ], ['occurredAt' => 'ASC', 'id' => 'ASC']),
        );
    }

    public function findByStore(OrganizationId $organizationId, StoreId $storeId): array
    {
        return array_map(
            fn(StockValuationMovementRecord $record): StockValuationMovement => $this->domain($record),
            $this->em->getRepository(StockValuationMovementRecord::class)->findBy([
                'organizationId' => $organizationId->toString(),
                'storeId' => $storeId->toString(),
            ], ['occurredAt' => 'ASC', 'id' => 'ASC']),
        );
    }

    /** @return list<string|null> */
    private function parameters(StockValuationMovement $movement): array
    {
        return [
            $movement->id()->toString(),
            $movement->stockValuationId()->toString(),
            $movement->organizationId()->toString(),
            $movement->storeId()->toString(),
            $movement->productId()->toString(),
            $movement->stockId()->toString(),
            $movement->stockMovementId()?->toString(),
            $movement->type()->value,
            $movement->quantity()->toString(),
            $movement->unitCost()->amount()->toString(),
            $movement->value()->amount()->toString(),
            $movement->previousTotalValue()->amount()->toString(),
            $movement->resultingTotalValue()->amount()->toString(),
            $movement->previousAverageCost()->amount()->toString(),
            $movement->resultingAverageCost()->amount()->toString(),
            $movement->value()->currency()->code(),
            $movement->source()->type(),
            $movement->source()->referenceId(),
            $movement->occurredAt()->format(DATE_ATOM),
            $movement->correlationId()->toString(),
        ];
    }

    private function domain(StockValuationMovementRecord $record): StockValuationMovement
    {
        $currency = Currency::fromCode($record->currency());
        $money = fn(string $amount): Money => Money::fromString($amount, $currency, $this->decimals);

        return StockValuationMovement::record(
            StockValuationMovementId::fromString($record->id(), $this->uuids),
            StockValuationId::fromString($record->stockValuationId(), $this->uuids),
            OrganizationId::fromString($record->organizationId(), $this->uuids),
            StoreId::fromString($record->storeId(), $this->uuids),
            ProductId::fromString($record->productId(), $this->uuids),
            StockId::fromString($record->stockId(), $this->uuids),
            null === $record->stockMovementId() ? null : StockMovementId::fromString($record->stockMovementId(), $this->uuids),
            StockValuationMovementType::from($record->type()),
            Quantity::fromString($record->quantity(), $this->decimals),
            $money($record->unitCost()),
            $money($record->value()),
            $money($record->previousTotalValue()),
            $money($record->resultingTotalValue()),
            $money($record->previousAverageCost()),
            $money($record->resultingAverageCost()),
            StockValuationMovementSource::from($record->sourceType(), $record->sourceReferenceId()),
            $record->occurredAt(),
            CorrelationId::fromString($record->correlationId(), $this->uuids),
        );
    }
}
