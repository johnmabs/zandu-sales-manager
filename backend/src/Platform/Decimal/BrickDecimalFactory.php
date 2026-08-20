<?php

declare(strict_types=1);

namespace Zandu\Platform\Decimal;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\NumberFormatException;
use InvalidArgumentException;
use Zandu\SharedKernel\Decimal\Decimal;
use Zandu\SharedKernel\Decimal\DecimalFactory;

final class BrickDecimalFactory implements DecimalFactory
{
    public function fromString(string $value): Decimal
    {
        try {
            return new BrickDecimal(BigDecimal::of($value));
        } catch (NumberFormatException $exception) {
            throw new InvalidArgumentException('The value must be a valid decimal string.', previous: $exception);
        }
    }
}
