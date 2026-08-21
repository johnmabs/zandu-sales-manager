<?php

declare(strict_types=1);

namespace Zandu\Platform\Decimal;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode as BrickRoundingMode;
use Zandu\SharedKernel\Decimal\Decimal;
use Zandu\SharedKernel\Decimal\RoundingMode;

final readonly class BrickDecimal implements Decimal
{
    public function __construct(private BigDecimal $value) {}

    public function add(Decimal $other): Decimal
    {
        return new self($this->value->plus(self::valueOf($other)));
    }

    public function subtract(Decimal $other): Decimal
    {
        return new self($this->value->minus(self::valueOf($other)));
    }

    public function multiply(Decimal $other): Decimal
    {
        return new self($this->value->multipliedBy(self::valueOf($other)));
    }

    public function divide(Decimal $divisor, int $scale, RoundingMode $roundingMode): Decimal
    {
        return new self($this->value->dividedBy(
            self::valueOf($divisor),
            $scale,
            self::roundingMode($roundingMode),
        ));
    }

    public function withScale(int $scale, RoundingMode $roundingMode): Decimal
    {
        return new self($this->value->toScale($scale, self::roundingMode($roundingMode)));
    }

    public function compareTo(Decimal $other): int
    {
        return $this->value->compareTo(self::valueOf($other));
    }

    public function equals(Decimal $other): bool
    {
        return $this->value->isEqualTo(self::valueOf($other));
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
        return (string) $this->value;
    }

    private static function valueOf(Decimal $decimal): BigDecimal
    {
        return $decimal instanceof self ? $decimal->value : BigDecimal::of($decimal->toString());
    }

    private static function roundingMode(RoundingMode $roundingMode): BrickRoundingMode
    {
        return match ($roundingMode) {
            RoundingMode::Unnecessary => BrickRoundingMode::Unnecessary,
            RoundingMode::Up => BrickRoundingMode::Up,
            RoundingMode::Down => BrickRoundingMode::Down,
            RoundingMode::Ceiling => BrickRoundingMode::Ceiling,
            RoundingMode::Floor => BrickRoundingMode::Floor,
            RoundingMode::HalfUp => BrickRoundingMode::HalfUp,
            RoundingMode::HalfDown => BrickRoundingMode::HalfDown,
            RoundingMode::HalfEven => BrickRoundingMode::HalfEven,
        };
    }
}
