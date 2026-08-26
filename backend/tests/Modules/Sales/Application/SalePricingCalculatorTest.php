<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Sales\Application;

use PHPUnit\Framework\TestCase;
use Zandu\Modules\Sales\Application\SalePricingCalculator;
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\SharedKernel\Money\{Currency,Money};
use Zandu\SharedKernel\Quantity\Quantity;

final class SalePricingCalculatorTest extends TestCase
{
    public function testLineTotalUsesExactDecimalMultiplication(): void
    {
        $calculator = new SalePricingCalculator();
        $total = $calculator->calculateLineTotal(Quantity::fromString('2.5', new BrickDecimalFactory()), Money::fromString('1250.40', Currency::fromCode('XAF'), new BrickDecimalFactory()));
        self::assertSame('3126.000000000000', $total->amount()->toString());
        self::assertSame('XAF', $total->currency()->code());
    }
}
