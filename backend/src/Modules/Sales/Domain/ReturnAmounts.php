<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Domain;

use LogicException;
use Zandu\SharedKernel\Money\Money;

final readonly class ReturnAmounts
{
    public function __construct(
        private Money $discountAmount,
        private Money $taxableAmount,
        private Money $taxAmount,
        private Money $subtotal,
        private Money $total,
    ) {
        foreach ([$discountAmount, $taxableAmount, $taxAmount, $subtotal, $total] as $amount) {
            if ($amount->amount()->isNegative()) {
                throw new LogicException('Return amounts cannot be negative.');
            }
            if (!$amount->currency()->equals($total->currency())) {
                throw new LogicException('Return amounts must use one currency.');
            }
        }
    }

    public function discountAmount(): Money
    {
        return $this->discountAmount;
    }

    public function taxableAmount(): Money
    {
        return $this->taxableAmount;
    }

    public function taxAmount(): Money
    {
        return $this->taxAmount;
    }

    public function subtotal(): Money
    {
        return $this->subtotal;
    }

    public function total(): Money
    {
        return $this->total;
    }
}
