<?php

declare(strict_types=1);

namespace Zandu\Tests\Integration\Spike;

use PHPUnit\Framework\Attributes\DataProvider;
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\SharedKernel\Decimal\RoundingMode;
use Zandu\Tests\Integration\PostgresTestCase;

final class NumericPrecisionTest extends PostgresTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->connection->executeStatement('TRUNCATE architecture_spike.numeric_roundtrip');
    }

    #[DataProvider('productPrecisionCorpus')]
    public function testExactDecimalRoundTripThroughPostgreSql(string $case, string $value, string $expected): void
    {
        $decimal = (new BrickDecimalFactory())->fromString($value);
        $this->connection->insert('architecture_spike.numeric_roundtrip', [
            'case_name' => $case,
            'value' => $decimal->toString(),
        ]);

        self::assertSame($expected, $this->connection->fetchOne(
            'SELECT value::text FROM architecture_spike.numeric_roundtrip WHERE case_name = ?',
            [$case],
        ));
    }

    public function testTaxDiscountAllocationCostingAndRefundCalculationsAreExplicit(): void
    {
        $factory = new BrickDecimalFactory();
        $net = $factory->fromString('100.00');
        $tax = $net->multiply($factory->fromString('0.075'))->withScale(2, RoundingMode::HalfUp);
        $discount = $net->multiply($factory->fromString('0.125'))->withScale(2, RoundingMode::HalfUp);
        $allocation = $factory->fromString('10.00')->divide($factory->fromString('3'), 2, RoundingMode::Down);
        $allocated = $allocation->multiply($factory->fromString('3'));
        $residue = $factory->fromString('10.00')->subtract($allocated);
        $unitCost = $factory->fromString('100.00')->divide($factory->fromString('3'), 12, RoundingMode::HalfUp);
        $refund = $net->subtract($discount)->add($tax)->withScale(2, RoundingMode::HalfUp);

        self::assertSame('7.50', $tax->toString());
        self::assertSame('12.50', $discount->toString());
        self::assertSame('3.33', $allocation->toString());
        self::assertSame('0.01', $residue->toString());
        self::assertSame('33.333333333333', $unitCost->toString());
        self::assertSame('95.00', $refund->toString());
    }

    /**
     * @return iterable<string,array{string,string,string}>
     */
    public static function productPrecisionCorpus(): iterable
    {
        yield 'unit' => ['unit', '1', '1.000000000000'];
        yield 'kilogram' => ['kg', '2.375', '2.375000000000'];
        yield 'gram' => ['g', '0.001', '0.001000000000'];
        yield 'liter' => ['liter', '1.125', '1.125000000000'];
        yield 'meter' => ['meter', '3.333333', '3.333333000000'];
        yield 'carton' => ['carton', '24', '24.000000000000'];
        yield 'fractional packaging' => ['fractional_packaging', '0.083333333333', '0.083333333333'];
        yield 'large exact value' => ['large', '999999999999999999.123456789012', '999999999999999999.123456789012'];
    }
}
