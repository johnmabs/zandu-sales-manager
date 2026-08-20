<?php

declare(strict_types=1);

namespace Zandu\SharedKernel\Decimal;

interface Decimal
{
    public function add(self $other): self;

    public function subtract(self $other): self;

    public function multiply(self $other): self;

    public function divide(self $divisor, int $scale, RoundingMode $roundingMode): self;

    public function withScale(int $scale, RoundingMode $roundingMode): self;

    public function compareTo(self $other): int;

    public function equals(self $other): bool;

    public function isZero(): bool;

    public function isNegative(): bool;

    public function toString(): string;
}
