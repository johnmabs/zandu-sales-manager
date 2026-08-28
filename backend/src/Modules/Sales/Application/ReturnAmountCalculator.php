<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application;

use Zandu\Modules\Sales\Domain\{ReturnAmounts, SaleLine, SalesRuleViolation};
use Zandu\SharedKernel\Decimal\RoundingMode;
use Zandu\SharedKernel\Money\Money;
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class ReturnAmountCalculator
{
    private const int AMOUNT_SCALE = 12;
    private const int INTERMEDIATE_SCALE = 24;

    public function calculate(
        SaleLine $originalLine,
        Quantity $previouslyReturnedQuantity,
        Quantity $returnedQuantity,
    ): ReturnAmounts {
        if ($previouslyReturnedQuantity->isNegative()) {
            throw SalesRuleViolation::with('RETURN_CUMULATIVE_QUANTITY_INVALID', 'Previously returned quantity cannot be negative.');
        }
        if ($returnedQuantity->isZero() || $returnedQuantity->isNegative()) {
            throw SalesRuleViolation::with('RETURN_QUANTITY_INVALID', 'Return quantity must be greater than zero.');
        }

        $cumulativeQuantity = $previouslyReturnedQuantity->add($returnedQuantity);
        $originalQuantity = $originalLine->baseQuantity();
        if ($cumulativeQuantity->compareTo($originalQuantity) > 0) {
            throw SalesRuleViolation::with('RETURN_QUANTITY_EXCEEDS_SOLD', 'Cumulative return quantity cannot exceed the sold quantity.');
        }

        return new ReturnAmounts(
            $this->allocate($originalLine->discountAmount(), $originalQuantity, $previouslyReturnedQuantity, $cumulativeQuantity),
            $this->allocate($originalLine->taxableAmount(), $originalQuantity, $previouslyReturnedQuantity, $cumulativeQuantity),
            $this->allocate($originalLine->taxAmount(), $originalQuantity, $previouslyReturnedQuantity, $cumulativeQuantity),
            $this->allocate($originalLine->subtotal(), $originalQuantity, $previouslyReturnedQuantity, $cumulativeQuantity),
            $this->allocate($originalLine->total(), $originalQuantity, $previouslyReturnedQuantity, $cumulativeQuantity),
        );
    }

    private function allocate(
        Money $originalAmount,
        Quantity $originalQuantity,
        Quantity $previousQuantity,
        Quantity $cumulativeQuantity,
    ): Money {
        $before = $this->cumulativeTarget($originalAmount, $originalQuantity, $previousQuantity);
        $after = $cumulativeQuantity->equals($originalQuantity)
            ? $originalAmount
            : $this->cumulativeTarget($originalAmount, $originalQuantity, $cumulativeQuantity);

        return $after->subtract($before)->withScale(self::AMOUNT_SCALE, RoundingMode::HalfUp);
    }

    private function cumulativeTarget(Money $originalAmount, Quantity $originalQuantity, Quantity $quantity): Money
    {
        if ($quantity->isZero()) {
            return $originalAmount->subtract($originalAmount)->withScale(self::AMOUNT_SCALE, RoundingMode::HalfUp);
        }

        return $originalAmount
            ->multiply($quantity->value(), self::INTERMEDIATE_SCALE, RoundingMode::HalfUp)
            ->divide($originalQuantity->value(), self::AMOUNT_SCALE, RoundingMode::HalfUp);
    }
}
