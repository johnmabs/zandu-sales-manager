<?php

declare(strict_types=1);

namespace Zandu\Modules\InventoryCosting\Application;

use Zandu\Modules\InventoryCosting\Application\Contract\{InventoryCostingMovementType, InventoryMovementValuer, ValueInventoryMovement, ValuedInventoryMovement};
use Zandu\Modules\InventoryCosting\Domain\InventoryCostingRuleViolation;
use Zandu\Modules\InventoryCosting\Domain\Valuation\{MovingWeightedAverageCalculator, MovingWeightedAverageResult, StockValuation, StockValuationRepository};
use Zandu\Modules\InventoryCosting\Domain\ValuationMovement\{StockValuationMovement, StockValuationMovementRepository, StockValuationMovementSource, StockValuationMovementType};
use Zandu\Modules\Organization\Application\Contract\StoreBusinessContextProvider;
use Zandu\SharedKernel\Decimal\RoundingMode;
use Zandu\SharedKernel\Identity\{IdGenerator, StockValuationId, StockValuationMovementId};
use Zandu\SharedKernel\Money\{Currency, Money};

final readonly class RepositoryInventoryMovementValuer implements InventoryMovementValuer
{
    public function __construct(
        private StockValuationRepository $valuations,
        private StockValuationMovementRepository $movements,
        private StoreBusinessContextProvider $stores,
        private MovingWeightedAverageCalculator $calculator,
        private IdGenerator $ids,
    ) {}

    public function value(ValueInventoryMovement $movement): ValuedInventoryMovement
    {
        $organizationId = $movement->actorContext->organizationId();
        $currency = Currency::fromCode($this->stores->provide($organizationId, $movement->storeId)->currency);
        $incomingUnitCost = null !== $movement->incomingUnitCost
            ? (new Money($movement->incomingUnitCost, $currency))->withScale(12, RoundingMode::HalfEven)
            : null;
        $this->assertCostPolicy($movement->type, $incomingUnitCost);

        if (InventoryCostingMovementType::InitialStock === $movement->type) {
            return $this->initialize($movement, $this->requiredIncomingCost($incomingUnitCost));
        }

        $valuation = $this->valuations->getByStockForUpdate($organizationId, $movement->stockId);
        if (!$valuation->quantityOnHand()->equals($movement->previousQuantity)) {
            throw InventoryCostingRuleViolation::with(
                'VALUATION_STOCK_QUANTITY_MISMATCH',
                'Stock valuation quantity does not match the physical stock quantity.',
            );
        }

        $previousTotal = $valuation->totalValue();
        $previousAverage = $valuation->averageUnitCost();
        $result = InventoryCostingMovementType::AdjustmentIn === $movement->type
            ? $valuation->receive($movement->quantity, $this->requiredIncomingCost($incomingUnitCost), $this->calculator)
            : $valuation->issue($movement->quantity, $this->calculator);
        $this->assertResultingQuantity($movement, $result);
        $this->valuations->save($valuation);
        $this->movements->append($this->ledger($movement, $valuation, $result, $previousTotal, $previousAverage));

        return $this->result($movement, $valuation, $result);
    }

    private function initialize(ValueInventoryMovement $movement, Money $incomingUnitCost): ValuedInventoryMovement
    {
        $organizationId = $movement->actorContext->organizationId();
        if (null !== $this->valuations->findByStock($organizationId, $movement->stockId)) {
            throw InventoryCostingRuleViolation::with(
                'VALUATION_ALREADY_INITIALIZED',
                'Stock valuation is already initialized.',
            );
        }
        if (!$movement->previousQuantity->isZero() || !$movement->resultingQuantity->equals($movement->quantity)) {
            throw InventoryCostingRuleViolation::with(
                'VALUATION_STOCK_QUANTITY_MISMATCH',
                'Initial stock movement quantities are inconsistent.',
            );
        }

        $valuation = StockValuation::initialize(
            StockValuationId::generate($this->ids),
            $organizationId,
            $movement->storeId,
            $movement->productId,
            $movement->stockId,
            $movement->resultingQuantity,
            $incomingUnitCost,
            $this->calculator,
        );
        $zeroValue = $incomingUnitCost->subtract($incomingUnitCost)->withScale(6, RoundingMode::HalfEven);
        $zeroAverage = $incomingUnitCost->subtract($incomingUnitCost)->withScale(12, RoundingMode::HalfEven);
        $result = new MovingWeightedAverageResult(
            $valuation->quantityOnHand(),
            $valuation->totalValue(),
            $incomingUnitCost,
            $valuation->totalValue(),
            $valuation->averageUnitCost(),
        );
        $this->valuations->save($valuation);
        $this->movements->append($this->ledger($movement, $valuation, $result, $zeroValue, $zeroAverage));

        return $this->result($movement, $valuation, $result);
    }

    private function ledger(
        ValueInventoryMovement $movement,
        StockValuation $valuation,
        MovingWeightedAverageResult $result,
        Money $previousTotal,
        Money $previousAverage,
    ): StockValuationMovement {
        $type = match ($movement->type) {
            InventoryCostingMovementType::InitialStock => StockValuationMovementType::InitialStock,
            InventoryCostingMovementType::AdjustmentIn => StockValuationMovementType::AdjustmentIn,
            InventoryCostingMovementType::AdjustmentOut => StockValuationMovementType::AdjustmentOut,
            InventoryCostingMovementType::Sale => StockValuationMovementType::Sale,
        };

        return StockValuationMovement::record(
            StockValuationMovementId::generate($this->ids),
            $valuation->id(),
            $movement->actorContext->organizationId(),
            $movement->storeId,
            $movement->productId,
            $movement->stockId,
            $movement->stockMovementId,
            $type,
            $movement->quantity,
            $result->movementUnitCost,
            $result->movementValue,
            $previousTotal,
            $result->resultingTotalValue,
            $previousAverage,
            $result->resultingAverageUnitCost,
            StockValuationMovementSource::from($type->sourceType(), $movement->reason),
            $movement->occurredAt,
            $movement->actorContext->correlationId(),
        );
    }

    private function assertCostPolicy(InventoryCostingMovementType $type, ?Money $incomingUnitCost): void
    {
        if ($type->isIncoming() && null === $incomingUnitCost) {
            throw InventoryCostingRuleViolation::with(
                'VALUATION_UNIT_COST_REQUIRED',
                'An explicit unit cost is required for an incoming inventory movement.',
            );
        }
        if (!$type->isIncoming() && null !== $incomingUnitCost) {
            throw InventoryCostingRuleViolation::with(
                'VALUATION_UNIT_COST_UNEXPECTED',
                'An outgoing inventory movement uses the current average cost.',
            );
        }
    }

    private function requiredIncomingCost(?Money $incomingUnitCost): Money
    {
        return $incomingUnitCost ?? throw InventoryCostingRuleViolation::with(
            'VALUATION_UNIT_COST_REQUIRED',
            'An explicit unit cost is required for an incoming inventory movement.',
        );
    }

    private function assertResultingQuantity(ValueInventoryMovement $movement, MovingWeightedAverageResult $result): void
    {
        if (!$result->resultingQuantity->equals($movement->resultingQuantity)) {
            throw InventoryCostingRuleViolation::with(
                'VALUATION_STOCK_QUANTITY_MISMATCH',
                'Stock valuation result does not match the physical stock result.',
            );
        }
    }

    private function result(
        ValueInventoryMovement $movement,
        StockValuation $valuation,
        MovingWeightedAverageResult $result,
    ): ValuedInventoryMovement {
        return new ValuedInventoryMovement(
            $movement->stockId,
            $movement->stockMovementId,
            $movement->quantity,
            $result->movementUnitCost,
            $result->movementValue,
            $valuation->version(),
            $movement->occurredAt,
        );
    }
}
