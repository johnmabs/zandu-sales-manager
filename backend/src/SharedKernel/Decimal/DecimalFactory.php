<?php

declare(strict_types=1);

namespace Zandu\SharedKernel\Decimal;

interface DecimalFactory
{
    public function fromString(string $value): Decimal;
}
