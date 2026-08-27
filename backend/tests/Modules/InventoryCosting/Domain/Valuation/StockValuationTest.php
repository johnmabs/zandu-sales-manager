<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\InventoryCosting\Domain\Valuation;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\InventoryCosting\Domain\InventoryCostingRuleViolation;
use Zandu\Modules\InventoryCosting\Domain\Valuation\{MovingWeightedAverageCalculator, StockValuation};
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\{OrganizationId, ProductId, StockId, StockValuationId, StoreId};
use Zandu\SharedKernel\Money\{Currency, CurrencyMismatch, Money};
use Zandu\SharedKernel\Quantity\Quantity;

final class StockValuationTest extends TestCase
{
    private const VALUATION_ID = '0198f101-1111-7111-8111-111111111111';
    private const ORGANIZATION_ID = '0198f102-1111-7111-8111-111111111111';
    private const STORE_ID = '0198f103-1111-7111-8111-111111111111';
    private const PRODUCT_ID = '0198f104-1111-7111-8111-111111111111';
    private const STOCK_ID = '0198f105-1111-7111-8111-111111111111';

    private SymfonyUuidFactory $ids;
    private BrickDecimalFactory $decimals;
    private MovingWeightedAverageCalculator $calculator;

    protected function setUp(): void
    {
        $this->ids = new SymfonyUuidFactory();
        $this->decimals = new BrickDecimalFactory();
        $this->calculator = new MovingWeightedAverageCalculator();
    }

    public function testItInitializesAnOwnedValuationAtTheOpeningCost(): void
    {
        $valuation = $this->valuation('10', '4000');

        self::assertSame(self::VALUATION_ID, $valuation->id()->toString());
        self::assertSame(self::ORGANIZATION_ID, $valuation->organizationId()->toString());
        self::assertSame(self::STORE_ID, $valuation->storeId()->toString());
        self::assertSame(self::PRODUCT_ID, $valuation->productId()->toString());
        self::assertSame(self::STOCK_ID, $valuation->stockId()->toString());
        self::assertSame('10', $valuation->quantityOnHand()->toString());
        self::assertSame('40000.000000', $valuation->totalValue()->amount()->toString());
        self::assertSame('4000.000000000000', $valuation->averageUnitCost()->amount()->toString());
        self::assertSame('XAF', $valuation->currency()->code());
        self::assertSame(1, $valuation->version());
    }

    public function testZeroQuantityAlwaysInitializesWithExactlyZeroValue(): void
    {
        $valuation = $this->valuation('0', '1200');

        self::assertSame('0.000000', $valuation->totalValue()->amount()->toString());
        self::assertSame('0.000000000000', $valuation->averageUnitCost()->amount()->toString());
    }

    public function testReceivingStockUpdatesQuantityValueAverageAndVersion(): void
    {
        $valuation = $this->valuation('10', '4000');
        $result = $valuation->receive($this->quantity('10'), $this->money('6000'), $this->calculator);

        self::assertSame('20', $valuation->quantityOnHand()->toString());
        self::assertSame('100000.000000', $valuation->totalValue()->amount()->toString());
        self::assertSame('5000.000000000000', $valuation->averageUnitCost()->amount()->toString());
        self::assertSame('60000.000000', $result->movementValue->amount()->toString());
        self::assertSame(2, $valuation->version());
    }

    public function testIssuingAllStockZerosQuantityAndValueExactly(): void
    {
        $valuation = $this->valuation('3', '3.333333666667');
        $result = $valuation->issue($this->quantity('3'), $this->calculator);

        self::assertSame('0', $valuation->quantityOnHand()->toString());
        self::assertSame('0.000000', $valuation->totalValue()->amount()->toString());
        self::assertSame('10.000001', $result->movementValue->amount()->toString());
        self::assertSame(2, $valuation->version());
    }

    public function testAFailedIssueDoesNotMutateTheAggregate(): void
    {
        $valuation = $this->valuation('2', '10');

        try {
            $valuation->issue($this->quantity('3'), $this->calculator);
            self::fail('An issue above valued quantity should fail.');
        } catch (InventoryCostingRuleViolation $violation) {
            self::assertSame('VALUATION_QUANTITY_INSUFFICIENT', $violation->errorCode());
        }

        self::assertSame('2', $valuation->quantityOnHand()->toString());
        self::assertSame('20.000000', $valuation->totalValue()->amount()->toString());
        self::assertSame(1, $valuation->version());
    }

    public function testReceivingStockInAnotherCurrencyIsRejectedWithoutMutation(): void
    {
        $valuation = $this->valuation('2', '10');

        try {
            $valuation->receive($this->quantity('1'), $this->money('10', 'EUR'), $this->calculator);
            self::fail('A valuation must retain its currency.');
        } catch (CurrencyMismatch) {
        }

        self::assertSame('2', $valuation->quantityOnHand()->toString());
        self::assertSame('20.000000', $valuation->totalValue()->amount()->toString());
        self::assertSame(1, $valuation->version());
    }

    public function testReconstitutionRejectsValueWithoutQuantity(): void
    {
        $this->expectException(InventoryCostingRuleViolation::class);
        $this->expectExceptionMessage('A zero valuation quantity must have a zero total value.');

        $this->reconstitute($this->quantity('0'), $this->money('1'), 1);
    }

    public function testReconstitutionRejectsANonPositiveVersion(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Stock valuation version must be positive.');

        $this->reconstitute($this->quantity('1'), $this->money('1'), 0);
    }

    private function valuation(string $quantity, string $unitCost): StockValuation
    {
        return StockValuation::initialize(
            StockValuationId::fromString(self::VALUATION_ID, $this->ids),
            OrganizationId::fromString(self::ORGANIZATION_ID, $this->ids),
            StoreId::fromString(self::STORE_ID, $this->ids),
            ProductId::fromString(self::PRODUCT_ID, $this->ids),
            StockId::fromString(self::STOCK_ID, $this->ids),
            $this->quantity($quantity),
            $this->money($unitCost),
            $this->calculator,
        );
    }

    private function reconstitute(Quantity $quantity, Money $totalValue, int $version): StockValuation
    {
        return StockValuation::reconstitute(
            StockValuationId::fromString(self::VALUATION_ID, $this->ids),
            OrganizationId::fromString(self::ORGANIZATION_ID, $this->ids),
            StoreId::fromString(self::STORE_ID, $this->ids),
            ProductId::fromString(self::PRODUCT_ID, $this->ids),
            StockId::fromString(self::STOCK_ID, $this->ids),
            $quantity,
            $totalValue,
            $version,
        );
    }

    private function quantity(string $value): Quantity
    {
        return Quantity::fromString($value, $this->decimals);
    }

    private function money(string $value, string $currency = 'XAF'): Money
    {
        return Money::fromString($value, Currency::fromCode($currency), $this->decimals);
    }
}
