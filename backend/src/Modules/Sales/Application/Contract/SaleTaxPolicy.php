<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application\Contract;

use Zandu\SharedKernel\Money\Money;

interface SaleTaxPolicy
{
    public function calculate(Money $netAmount): SaleTaxCalculation;
}
