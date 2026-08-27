<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application\Contract;

use Zandu\SharedKernel\Money\Money;

final readonly class SaleTaxCalculation
{
    public function __construct(public Money $taxableAmount, public Money $taxAmount, public string $policyCode) {}
}
