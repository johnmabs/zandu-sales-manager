<?php

declare(strict_types=1);

namespace Zandu\Modules\InventoryCosting\Domain\Valuation;

use Zandu\Modules\InventoryCosting\Domain\InventoryCostingRuleViolation;
use Zandu\SharedKernel\Decimal\RoundingMode;
use Zandu\SharedKernel\Money\Money;
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class MovingWeightedAverageCalculator
{
    private const int VALUE_SCALE = 6;
    private const int UNIT_COST_SCALE = 12;

    public function add(
        Quantity $previousQuantity,
        Money $previousTotalValue,
        Quantity $incomingQuantity,
        Money $incomingUnitCost,
    ): MovingWeightedAverageResult {
        $this->assertState($previousQuantity, $previousTotalValue);
        $this->assertPositiveMovement($incomingQuantity);
        if ($incomingUnitCost->amount()->isNegative()) {
            throw InventoryCostingRuleViolation::with(
                'VALUATION_UNIT_COST_NEGATIVE',
                'Inventory unit cost cannot be negative.',
            );
        }

        $unitCost = $incomingUnitCost->withScale(self::UNIT_COST_SCALE, RoundingMode::HalfEven);
        $movementValue = $unitCost->multiply(
            $incomingQuantity->value(),
            self::VALUE_SCALE,
            RoundingMode::HalfEven,
        );
        $resultingQuantity = $previousQuantity->add($incomingQuantity);
        $resultingTotalValue = $previousTotalValue
            ->withScale(self::VALUE_SCALE, RoundingMode::HalfEven)
            ->add($movementValue);

        return new MovingWeightedAverageResult(
            $resultingQuantity,
            $resultingTotalValue,
            $unitCost,
            $movementValue,
            $this->average($resultingTotalValue, $resultingQuantity),
        );
    }

    public function remove(
        Quantity $previousQuantity,
        Money $previousTotalValue,
        Quantity $outgoingQuantity,
    ): MovingWeightedAverageResult {
        $this->assertState($previousQuantity, $previousTotalValue);
        $this->assertPositiveMovement($outgoingQuantity);
        if ($outgoingQuantity->compareTo($previousQuantity) > 0) {
            throw InventoryCostingRuleViolation::with(
                'VALUATION_QUANTITY_INSUFFICIENT',
                'Valuation quantity is insufficient for this movement.',
            );
        }

        $normalizedTotalValue = $previousTotalValue->withScale(self::VALUE_SCALE, RoundingMode::HalfEven);
        $unitCost = $this->average($normalizedTotalValue, $previousQuantity);
        $resultingQuantity = $previousQuantity->subtract($outgoingQuantity);
        $movementValue = $resultingQuantity->isZero()
            ? $normalizedTotalValue
            : $unitCost->multiply($outgoingQuantity->value(), self::VALUE_SCALE, RoundingMode::HalfEven);
        $resultingTotalValue = $normalizedTotalValue->subtract($movementValue);

        return new MovingWeightedAverageResult(
            $resultingQuantity,
            $resultingTotalValue,
            $unitCost,
            $movementValue,
            $this->average($resultingTotalValue, $resultingQuantity),
        );
    }

    private function assertState(Quantity $quantity, Money $totalValue): void
    {
        if ($quantity->isNegative() || $totalValue->amount()->isNegative()) {
            throw InventoryCostingRuleViolation::with(
                'VALUATION_STATE_INVALID',
                'Valuation quantity and total value cannot be negative.',
            );
        }
        if ($quantity->isZero() && !$totalValue->amount()->isZero()) {
            throw InventoryCostingRuleViolation::with(
                'VALUATION_STATE_INVALID',
                'A zero valuation quantity must have a zero total value.',
            );
        }
    }

    private function assertPositiveMovement(Quantity $quantity): void
    {
        if ($quantity->isZero() || $quantity->isNegative()) {
            throw InventoryCostingRuleViolation::with(
                'VALUATION_MOVEMENT_QUANTITY_INVALID',
                'Valuation movement quantity must be positive.',
            );
        }
    }

    private function average(Money $totalValue, Quantity $quantity): Money
    {
        if ($quantity->isZero()) {
            return $totalValue->withScale(self::UNIT_COST_SCALE, RoundingMode::HalfEven);
        }

        return $totalValue->divide($quantity->value(), self::UNIT_COST_SCALE, RoundingMode::HalfEven);
    }
}
