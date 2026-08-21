<?php

declare(strict_types=1);

namespace Zandu\Tests\SharedKernel\Decimal;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionType;
use Zandu\SharedKernel\Decimal\Decimal;
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Decimal\RoundingMode;

final class DecimalContractsTest extends TestCase
{
    public function testDecimalContractExposesTheRequiredOperations(): void
    {
        $reflection = new ReflectionClass(Decimal::class);

        self::assertTrue($reflection->isInterface());
        self::assertSame(
            [
                'add',
                'subtract',
                'multiply',
                'divide',
                'withScale',
                'compareTo',
                'equals',
                'isZero',
                'isNegative',
                'toString',
            ],
            array_map(
                static fn(ReflectionMethod $method): string => $method->getName(),
                $reflection->getMethods(),
            ),
        );
    }

    public function testDecimalFactoryOnlyAcceptsAStringRepresentation(): void
    {
        $method = new ReflectionMethod(DecimalFactory::class, 'fromString');

        self::assertSame('string', self::typeName($method->getParameters()[0]->getType()));
        self::assertSame(Decimal::class, self::typeName($method->getReturnType()));
    }

    public function testLossyOperationsRequireAnExplicitRoundingMode(): void
    {
        $divide = new ReflectionMethod(Decimal::class, 'divide');
        $withScale = new ReflectionMethod(Decimal::class, 'withScale');

        self::assertSame(RoundingMode::class, self::typeName($divide->getParameters()[2]->getType()));
        self::assertSame(RoundingMode::class, self::typeName($withScale->getParameters()[1]->getType()));
    }

    /**
     * @param class-string $contract
     */
    #[DataProvider('contracts')]
    public function testContractsDoNotExposeFloatOrBrickMath(string $contract): void
    {
        $reflection = new ReflectionClass($contract);

        foreach ($reflection->getMethods() as $method) {
            self::assertPortableType($method->getReturnType());

            foreach ($method->getParameters() as $parameter) {
                self::assertPortableType($parameter->getType());
            }
        }
    }

    public function testRoundingModesAreExplicitAndTechnologyAgnostic(): void
    {
        self::assertSame(
            [
                'Unnecessary',
                'Up',
                'Down',
                'Ceiling',
                'Floor',
                'HalfUp',
                'HalfDown',
                'HalfEven',
            ],
            array_column(RoundingMode::cases(), 'name'),
        );
    }

    /**
     * @return iterable<string, array{class-string}>
     */
    public static function contracts(): iterable
    {
        yield 'decimal' => [Decimal::class];
        yield 'decimal factory' => [DecimalFactory::class];
    }

    private static function assertPortableType(?ReflectionType $type): void
    {
        if (!$type instanceof ReflectionNamedType) {
            return;
        }

        self::assertNotSame('float', $type->getName());
        self::assertStringStartsNotWith('Brick\\Math\\', $type->getName());
    }

    private static function typeName(?ReflectionType $type): ?string
    {
        return $type instanceof ReflectionNamedType ? $type->getName() : null;
    }
}
