<?php

declare(strict_types=1);

namespace Zandu\SharedKernel\Money;

use Zandu\SharedKernel\Decimal\Decimal;
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Decimal\RoundingMode;

final readonly class Money
{
    public function __construct(
        private Decimal $amount,
        private Currency $currency,
    ) {}

    public static function fromString(
        string $amount,
        Currency $currency,
        DecimalFactory $decimalFactory,
    ): self {
        return new self($decimalFactory->fromString($amount), $currency);
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount->add($other->amount), $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount->subtract($other->amount), $this->currency);
    }

    public function multiply(
        Decimal $multiplier,
        int $scale,
        RoundingMode $roundingMode,
    ): self {
        return new self(
            $this->amount->multiply($multiplier)->withScale($scale, $roundingMode),
            $this->currency,
        );
    }

    public function divide(
        Decimal $divisor,
        int $scale,
        RoundingMode $roundingMode,
    ): self {
        return new self(
            $this->amount->divide($divisor, $scale, $roundingMode),
            $this->currency,
        );
    }

    public function withScale(int $scale, RoundingMode $roundingMode): self
    {
        return new self($this->amount->withScale($scale, $roundingMode), $this->currency);
    }

    public function compareTo(self $other): int
    {
        $this->assertSameCurrency($other);

        return $this->amount->compareTo($other->amount);
    }

    public function equals(self $other): bool
    {
        return $this->currency->equals($other->currency) && $this->amount->equals($other->amount);
    }

    public function amount(): Decimal
    {
        return $this->amount;
    }

    public function currency(): Currency
    {
        return $this->currency;
    }

    private function assertSameCurrency(self $other): void
    {
        if (!$this->currency->equals($other->currency)) {
            throw CurrencyMismatch::between($this->currency, $other->currency);
        }
    }
}
