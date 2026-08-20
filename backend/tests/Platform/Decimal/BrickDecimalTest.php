<?php

declare(strict_types=1);

namespace Zandu\Tests\Platform\Decimal;

use Brick\Math\Exception\RoundingNecessaryException;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\SharedKernel\Decimal\Decimal;
use Zandu\SharedKernel\Decimal\RoundingMode;

final class BrickDecimalTest extends TestCase
{
    public function testFactoryRejectsInvalidDecimalString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The value must be a valid decimal string.');

        $this->decimal('not-a-decimal');
    }

    public function testExactArithmeticDoesNotUseFloatingPoint(): void
    {
        self::assertSame('0.3', $this->decimal('0.1')->add($this->decimal('0.2'))->toString());
        self::assertSame('999.99', $this->decimal('1000')->subtract($this->decimal('0.01'))->toString());
        self::assertSame('12.50', $this->decimal('2.5')->multiply($this->decimal('5.0'))->toString());
    }

    public function testDivisionRequiresTheRequestedScaleAndRounding(): void
    {
        $result = $this->decimal('1')->divide($this->decimal('3'), 2, RoundingMode::HalfUp);

        self::assertSame('0.33', $result->toString());
    }

    public function testUnnecessaryRoundingRejectsAnInexactResult(): void
    {
        $this->expectException(RoundingNecessaryException::class);

        $this->decimal('1')->divide($this->decimal('3'), 2, RoundingMode::Unnecessary);
    }

    /**
     * @param non-empty-string $expected
     */
    #[DataProvider('roundingCases')]
    public function testEveryRoundingModeIsMapped(
        RoundingMode $roundingMode,
        string $value,
        string $expected,
    ): void {
        $result = $this->decimal($value)->withScale(2, $roundingMode);

        self::assertSame($expected, $result->toString());
    }

    public function testComparisonAndPredicatesAreNumeric(): void
    {
        self::assertTrue($this->decimal('1.0')->equals($this->decimal('1.00')));
        self::assertSame(-1, $this->decimal('1.99')->compareTo($this->decimal('2')));
        self::assertSame(0, $this->decimal('2.00')->compareTo($this->decimal('2')));
        self::assertSame(1, $this->decimal('2.01')->compareTo($this->decimal('2')));
        self::assertTrue($this->decimal('0.000')->isZero());
        self::assertTrue($this->decimal('-0.01')->isNegative());
        self::assertFalse($this->decimal('0')->isNegative());
    }

    /**
     * @return iterable<string, array{RoundingMode, non-empty-string, non-empty-string}>
     */
    public static function roundingCases(): iterable
    {
        yield 'up' => [RoundingMode::Up, '1.255', '1.26'];
        yield 'down' => [RoundingMode::Down, '1.255', '1.25'];
        yield 'ceiling' => [RoundingMode::Ceiling, '1.255', '1.26'];
        yield 'floor' => [RoundingMode::Floor, '1.255', '1.25'];
        yield 'half up' => [RoundingMode::HalfUp, '1.255', '1.26'];
        yield 'half down' => [RoundingMode::HalfDown, '1.255', '1.25'];
        yield 'half even' => [RoundingMode::HalfEven, '1.255', '1.26'];
        yield 'unnecessary' => [RoundingMode::Unnecessary, '1.25', '1.25'];
    }

    private function decimal(string $value): Decimal
    {
        return (new BrickDecimalFactory())->fromString($value);
    }
}
