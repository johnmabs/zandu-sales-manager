<?php

declare(strict_types=1);

namespace Zandu\Tests\SharedKernel\Quantity;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\SharedKernel\Decimal\Decimal;
use Zandu\SharedKernel\Decimal\RoundingMode;
use Zandu\SharedKernel\Quantity\Quantity;

final class QuantityTest extends TestCase
{
    private BrickDecimalFactory $decimalFactory;

    protected function setUp(): void
    {
        $this->decimalFactory = new BrickDecimalFactory();
    }

    public function testQuantityIsCreatedFromAnExactString(): void
    {
        $quantity = $this->quantity('12.345');

        self::assertSame('12.345', $quantity->toString());
        self::assertSame('12.345', $quantity->value()->toString());
    }

    public function testAdditionAndSubtractionAreExact(): void
    {
        $quantity = $this->quantity('0.1')->add($this->quantity('0.2'));

        self::assertSame('0.3', $quantity->toString());
        self::assertSame('-0.1', $quantity->subtract($this->quantity('0.4'))->toString());
    }

    public function testMultiplicationRequiresExplicitScaleAndRounding(): void
    {
        $result = $this->quantity('2.555')->multiply(
            $this->decimal('3'),
            2,
            RoundingMode::HalfEven,
        );

        self::assertSame('7.66', $result->toString());
    }

    public function testDivisionRequiresExplicitScaleAndRounding(): void
    {
        $result = $this->quantity('10')->divide(
            $this->decimal('3'),
            3,
            RoundingMode::HalfUp,
        );

        self::assertSame('3.333', $result->toString());
    }

    public function testScaleIsOnlyAppliedWhenExplicitlyRequested(): void
    {
        $quantity = $this->quantity('1.23456');

        self::assertSame('1.23456', $quantity->toString());
        self::assertSame('1.235', $quantity->withScale(3, RoundingMode::HalfUp)->toString());
    }

    public function testComparisonAndPredicatesAreNumeric(): void
    {
        self::assertTrue($this->quantity('1.0')->equals($this->quantity('1.00')));
        self::assertSame(-1, $this->quantity('1.99')->compareTo($this->quantity('2')));
        self::assertTrue($this->quantity('0.000')->isZero());
        self::assertTrue($this->quantity('-0.001')->isNegative());
    }

    public function testNegativeValuesRemainAvailableForSignedDomainDeltas(): void
    {
        $quantity = $this->quantity('-2.5');

        self::assertTrue($quantity->isNegative());
        self::assertSame('-2.5', $quantity->toString());
    }

    public function testQuantityDoesNotExposeFloat(): void
    {
        $reflection = new ReflectionClass(Quantity::class);

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

    private function quantity(string $value): Quantity
    {
        return Quantity::fromString($value, $this->decimalFactory);
    }

    private function decimal(string $value): Decimal
    {
        return $this->decimalFactory->fromString($value);
    }
}
