<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application;

use Zandu\SharedKernel\Decimal\RoundingMode;
use Zandu\SharedKernel\Money\Money;
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class SalePricingCalculator
{
    public function calculateLineTotal(Quantity $quantity, Money $unitPrice): Money
    {
        return $unitPrice->multiply($quantity->value(), 12, RoundingMode::HalfUp);
    }
}
