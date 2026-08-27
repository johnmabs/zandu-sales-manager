<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\InventoryCosting\Domain\Valuation;

use PHPUnit\Framework\TestCase;
use Zandu\Modules\InventoryCosting\Domain\InventoryCostingRuleViolation;
use Zandu\Modules\InventoryCosting\Domain\Valuation\MovingWeightedAverageCalculator;
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\SharedKernel\Money\{Currency, Money};
use Zandu\SharedKernel\Quantity\Quantity;

final class MovingWeightedAverageCalculatorTest extends TestCase
{
    private MovingWeightedAverageCalculator $calculator;
    private BrickDecimalFactory $decimals;

    protected function setUp(): void
    {
        $this->calculator = new MovingWeightedAverageCalculator();
        $this->decimals = new BrickDecimalFactory();
    }

    public function testItInitializesAValuationFromAnExactEntryCost(): void
    {
        $result = $this->calculator->add(
            $this->quantity('0'),
            $this->money('0'),
            $this->quantity('10'),
            $this->money('4000'),
        );

        self::assertSame('10', $result->resultingQuantity->toString());
        self::assertSame('40000.000000', $result->resultingTotalValue->amount()->toString());
        self::assertSame('4000.000000000000', $result->resultingAverageUnitCost->amount()->toString());
        self::assertSame('40000.000000', $result->movementValue->amount()->toString());
    }

    public function testItRecalculatesTheAverageAfterEntriesAtDifferentCosts(): void
    {
        $result = $this->calculator->add(
            $this->quantity('10'),
            $this->money('40000'),
            $this->quantity('10'),
            $this->money('6000'),
        );

        self::assertSame('20', $result->resultingQuantity->toString());
        self::assertSame('100000.000000', $result->resultingTotalValue->amount()->toString());
        self::assertSame('5000.000000000000', $result->resultingAverageUnitCost->amount()->toString());
    }

    public function testItValuesAPartialExitAtTheCurrentAverageCost(): void
    {
        $result = $this->calculator->remove(
            $this->quantity('10'),
            $this->money('40000'),
            $this->quantity('2'),
        );

        self::assertSame('8', $result->resultingQuantity->toString());
        self::assertSame('8000.000000', $result->movementValue->amount()->toString());
        self::assertSame('32000.000000', $result->resultingTotalValue->amount()->toString());
        self::assertSame('4000.000000000000', $result->resultingAverageUnitCost->amount()->toString());
    }

    public function testTheFinalExitAbsorbsAllValuationResidue(): void
    {
        $result = $this->calculator->remove(
            $this->quantity('3'),
            $this->money('10.000001'),
            $this->quantity('3'),
        );

        self::assertSame('0', $result->resultingQuantity->toString());
        self::assertSame('10.000001', $result->movementValue->amount()->toString());
        self::assertSame('0.000000', $result->resultingTotalValue->amount()->toString());
        self::assertSame('0.000000000000', $result->resultingAverageUnitCost->amount()->toString());
    }

    public function testItSupportsFractionalQuantitiesAndCostsWithoutFloat(): void
    {
        $result = $this->calculator->add(
            $this->quantity('1.5'),
            $this->money('6000'),
            $this->quantity('0.5'),
            $this->money('5000.123456789012'),
        );

        self::assertSame('2.0', $result->resultingQuantity->toString());
        self::assertSame('2500.061728', $result->movementValue->amount()->toString());
        self::assertSame('8500.061728', $result->resultingTotalValue->amount()->toString());
        self::assertSame('4250.030864000000', $result->resultingAverageUnitCost->amount()->toString());
    }

    public function testItKeepsDeterministicRoundingResiduesOnPartialExit(): void
    {
        $result = $this->calculator->remove(
            $this->quantity('3'),
            $this->money('10'),
            $this->quantity('1'),
        );

        self::assertSame('3.333333', $result->movementValue->amount()->toString());
        self::assertSame('6.666667', $result->resultingTotalValue->amount()->toString());
        self::assertSame('3.333333500000', $result->resultingAverageUnitCost->amount()->toString());
    }

    public function testItRejectsAnExitAboveTheValuedQuantity(): void
    {
        $this->expectException(InventoryCostingRuleViolation::class);
        $this->expectExceptionMessage('Valuation quantity is insufficient for this movement.');

        $this->calculator->remove($this->quantity('2'), $this->money('20'), $this->quantity('3'));
    }

    public function testItRejectsAZeroMovement(): void
    {
        $this->expectException(InventoryCostingRuleViolation::class);
        $this->expectExceptionMessage('Valuation movement quantity must be positive.');

        $this->calculator->add($this->quantity('0'), $this->money('0'), $this->quantity('0'), $this->money('1'));
    }

    public function testItRejectsValueWithoutQuantity(): void
    {
        $this->expectException(InventoryCostingRuleViolation::class);
        $this->expectExceptionMessage('A zero valuation quantity must have a zero total value.');

        $this->calculator->add($this->quantity('0'), $this->money('1'), $this->quantity('1'), $this->money('1'));
    }

    private function quantity(string $value): Quantity
    {
        return Quantity::fromString($value, $this->decimals);
    }

    private function money(string $value): Money
    {
        return Money::fromString($value, Currency::fromCode('XAF'), $this->decimals);
    }
}
