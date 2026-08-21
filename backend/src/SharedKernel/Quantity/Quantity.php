<?php

declare(strict_types=1);

namespace Zandu\SharedKernel\Quantity;

use Zandu\SharedKernel\Decimal\Decimal;
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Decimal\RoundingMode;

final readonly class Quantity
{
    public function __construct(private Decimal $value) {}

    public static function fromString(string $value, DecimalFactory $decimalFactory): self
    {
        return new self($decimalFactory->fromString($value));
    }

    public function add(self $other): self
    {
        return new self($this->value->add($other->value));
    }

    public function subtract(self $other): self
    {
        return new self($this->value->subtract($other->value));
    }

    public function multiply(
        Decimal $multiplier,
        int $scale,
        RoundingMode $roundingMode,
    ): self {
        return new self($this->value->multiply($multiplier)->withScale($scale, $roundingMode));
    }

    public function divide(
        Decimal $divisor,
        int $scale,
        RoundingMode $roundingMode,
    ): self {
        return new self($this->value->divide($divisor, $scale, $roundingMode));
    }

    public function withScale(int $scale, RoundingMode $roundingMode): self
    {
        return new self($this->value->withScale($scale, $roundingMode));
    }

    public function compareTo(self $other): int
    {
        return $this->value->compareTo($other->value);
    }

    public function equals(self $other): bool
    {
        return $this->value->equals($other->value);
    }

    public function isZero(): bool
    {
        return $this->value->isZero();
    }

    public function isNegative(): bool
    {
        return $this->value->isNegative();
    }

    public function toString(): string
    {
        return $this->value->toString();
    }

    public function value(): Decimal
    {
        return $this->value;
    }
}
