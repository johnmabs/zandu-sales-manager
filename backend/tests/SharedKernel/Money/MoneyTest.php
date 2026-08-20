<?php

declare(strict_types=1);

namespace Zandu\Tests\SharedKernel\Money;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\SharedKernel\Decimal\Decimal;
use Zandu\SharedKernel\Decimal\RoundingMode;
use Zandu\SharedKernel\Money\Currency;
use Zandu\SharedKernel\Money\CurrencyMismatch;
use Zandu\SharedKernel\Money\Money;

final class MoneyTest extends TestCase
{
    private BrickDecimalFactory $decimalFactory;

    protected function setUp(): void
    {
        $this->decimalFactory = new BrickDecimalFactory();
    }

    public function testCurrencyNormalizesItsCode(): void
    {
        $currency = Currency::fromCode('ngn');

        self::assertSame('NGN', $currency->code());
        self::assertTrue($currency->equals(Currency::fromCode('NGN')));
    }

    #[DataProvider('invalidCurrencyCodes')]
    public function testCurrencyRejectsInvalidCode(string $code): void
    {
        $this->expectException(InvalidArgumentException::class);

        Currency::fromCode($code);
    }

    public function testMoneyIsCreatedFromAnExactStringAmount(): void
    {
        $money = $this->money('1200.50', 'NGN');

        self::assertSame('1200.50', $money->amount()->toString());
        self::assertSame('NGN', $money->currency()->code());
    }

    public function testAdditionAndSubtractionPreserveCurrency(): void
    {
        $money = $this->money('0.10', 'NGN')->add($this->money('0.20', 'NGN'));

        self::assertSame('0.30', $money->amount()->toString());
        self::assertSame('0.20', $money->subtract($this->money('0.10', 'NGN'))->amount()->toString());
        self::assertSame('NGN', $money->currency()->code());
    }

    public function testOperationsBetweenCurrenciesAreRejected(): void
    {
        $this->expectException(CurrencyMismatch::class);
        $this->expectExceptionMessage('different currencies: NGN and USD');

        $this->money('100', 'NGN')->add($this->money('100', 'USD'));
    }

    public function testMultiplicationRequiresExplicitScaleAndRounding(): void
    {
        $result = $this->money('10.05', 'NGN')->multiply(
            $this->decimal('0.075'),
            2,
            RoundingMode::HalfUp,
        );

        self::assertSame('0.75', $result->amount()->toString());
    }

    public function testDivisionRequiresExplicitScaleAndRounding(): void
    {
        $result = $this->money('10', 'NGN')->divide(
            $this->decimal('3'),
            2,
            RoundingMode::HalfUp,
        );

        self::assertSame('3.33', $result->amount()->toString());
    }

    public function testEqualityAndComparisonAreCurrencyAware(): void
    {
        self::assertTrue($this->money('1.0', 'NGN')->equals($this->money('1.00', 'NGN')));
        self::assertFalse($this->money('1.00', 'NGN')->equals($this->money('1.00', 'USD')));
        self::assertSame(-1, $this->money('9.99', 'NGN')->compareTo($this->money('10', 'NGN')));
    }

    public function testMoneyDoesNotExposeFloat(): void
    {
        $reflection = new ReflectionClass(Money::class);

        foreach ($reflection->getMethods() as $method) {
            $returnType = $method->getReturnType();

            if ($returnType instanceof ReflectionNamedType) {
                self::assertNotSame('float', $returnType->getName());
            }

            foreach ($method->getParameters() as $parameter) {
                $type = $parameter->getType();

                if ($type instanceof ReflectionNamedType) {
                    self::assertNotSame('float', $type->getName());
                }
            }
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidCurrencyCodes(): iterable
    {
        yield 'empty' => [''];
        yield 'too short' => ['NG'];
        yield 'too long' => ['NGNN'];
        yield 'numeric' => ['123'];
        yield 'whitespace' => [' NGN'];
        yield 'non ASCII' => ['EU€'];
    }

    private function money(string $amount, string $currency): Money
    {
        return Money::fromString($amount, Currency::fromCode($currency), $this->decimalFactory);
    }

    private function decimal(string $value): Decimal
    {
        return $this->decimalFactory->fromString($value);
    }
}
