<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Sales\Application;

use PHPUnit\Framework\TestCase;
use Zandu\Modules\Sales\Application\ReturnAmountCalculator;
use Zandu\Modules\Sales\Domain\{SaleLine, SalesRuleViolation};
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\{ProductId, ProductPackagingId, SaleId, SaleLineId, UnitOfMeasureId};
use Zandu\SharedKernel\Money\{Currency, Money};
use Zandu\SharedKernel\Quantity\Quantity;

final class ReturnAmountCalculatorTest extends TestCase
{
    private BrickDecimalFactory $decimals;

    protected function setUp(): void
    {
        $this->decimals = new BrickDecimalFactory();
    }

    public function testItAllocatesEveryOriginalCommercialSnapshotProportionally(): void
    {
        $amounts = (new ReturnAmountCalculator())->calculate($this->line(), $this->quantity('0'), $this->quantity('1'));

        self::assertSame('0.333333333333', $amounts->discountAmount()->amount()->toString());
        self::assertSame('3.000000000000', $amounts->taxableAmount()->amount()->toString());
        self::assertSame('0.583333333333', $amounts->taxAmount()->amount()->toString());
        self::assertSame('3.333333333333', $amounts->subtotal()->amount()->toString());
        self::assertSame('3.583333333333', $amounts->total()->amount()->toString());
    }

    public function testSuccessivePartialReturnsAbsorbRoundingResidueAndEqualTheExactFullReturn(): void
    {
        $calculator = new ReturnAmountCalculator();
        $line = $this->line();
        $first = $calculator->calculate($line, $this->quantity('0'), $this->quantity('1'));
        $second = $calculator->calculate($line, $this->quantity('1'), $this->quantity('1'));
        $final = $calculator->calculate($line, $this->quantity('2'), $this->quantity('1'));

        self::assertSame('3.583333333334', $second->total()->amount()->toString());
        self::assertSame('3.583333333333', $final->total()->amount()->toString());
        self::assertTrue($line->total()->equals($first->total()->add($second->total())->add($final->total())));
        self::assertTrue($line->taxAmount()->equals($first->taxAmount()->add($second->taxAmount())->add($final->taxAmount())));
        self::assertTrue($line->discountAmount()->equals($first->discountAmount()->add($second->discountAmount())->add($final->discountAmount())));
    }

    public function testAFullReturnUsesTheExactOriginalSnapshots(): void
    {
        $line = $this->line();
        $amounts = (new ReturnAmountCalculator())->calculate($line, $this->quantity('0'), $this->quantity('3'));

        self::assertTrue($line->total()->equals($amounts->total()));
        self::assertTrue($line->taxAmount()->equals($amounts->taxAmount()));
        self::assertTrue($line->discountAmount()->equals($amounts->discountAmount()));
    }

    public function testItRejectsAnAllocationBeyondTheOriginalQuantity(): void
    {
        try {
            (new ReturnAmountCalculator())->calculate($this->line(), $this->quantity('2.5'), $this->quantity('1'));
            self::fail('An excessive cumulative return should be rejected.');
        } catch (SalesRuleViolation $exception) {
            self::assertSame('RETURN_QUANTITY_EXCEEDS_SOLD', $exception->errorCode());
        }
    }

    private function line(): SaleLine
    {
        $uuids = new SymfonyUuidFactory();
        $currency = Currency::fromCode('XAF');

        return new SaleLine(
            SaleLineId::fromString('019a3400-0000-7000-8000-000000000001', $uuids),
            SaleId::fromString('019a3400-0000-7000-8000-000000000002', $uuids),
            ProductId::fromString('019a3400-0000-7000-8000-000000000003', $uuids),
            ProductPackagingId::fromString('019a3400-0000-7000-8000-000000000004', $uuids),
            'SKU',
            'Product',
            'EA',
            'Each',
            UnitOfMeasureId::fromString('019a3400-0000-7000-8000-000000000005', $uuids),
            $this->quantity('3'),
            $this->quantity('1'),
            $this->quantity('3'),
            $this->money('4'),
            null,
            null,
            $this->money('1'),
            $this->money('9'),
            $this->money('1.75'),
            $this->money('10'),
            $this->money('10.75'),
        );
    }

    private function money(string $amount): Money
    {
        return Money::fromString($amount, Currency::fromCode('XAF'), $this->decimals);
    }

    private function quantity(string $quantity): Quantity
    {
        return Quantity::fromString($quantity, $this->decimals);
    }
}
