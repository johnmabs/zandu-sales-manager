<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application;

use LogicException;
use Zandu\Modules\Sales\Application\Contract\{SaleTaxCalculation,SaleTaxPolicy};
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Money\Money;

final readonly class ConfiguredSaleTaxPolicy implements SaleTaxPolicy
{
    public function __construct(private string $policy, private DecimalFactory $decimals) {}

    public function calculate(Money $netAmount): SaleTaxCalculation
    {
        if ('NO_TAX' !== strtoupper(trim($this->policy))) {
            throw new LogicException('Unsupported sale tax policy. Configure an explicit fiscal policy before selling.');
        }

        return new SaleTaxCalculation(
            $netAmount,
            new Money($this->decimals->fromString('0'), $netAmount->currency()),
            'NO_TAX',
        );
    }
}
