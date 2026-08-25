<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Domain\ProductPackaging;

use InvalidArgumentException;
use Zandu\SharedKernel\Decimal\Decimal;

final readonly class ConversionFactor
{
    public function __construct(private Decimal $value)
    {
        if ($value->isZero() || $value->isNegative()) {
            throw new InvalidArgumentException('Conversion factor must be greater than zero.');
        }
        self::assertMaximumScale($value->toString());
    }

    public function value(): Decimal
    {
        return $this->value;
    }

    public function toString(): string
    {
        return $this->value->toString();
    }

    public function equals(self $other): bool
    {
        return $this->value->equals($other->value);
    }

    private static function assertMaximumScale(string $value): void
    {
        $fraction = strchr($value, '.');
        if (false !== $fraction && strlen(rtrim(substr($fraction, 1), '0')) > 12) {
            throw new InvalidArgumentException('Conversion factor cannot exceed 12 decimal places.');
        }
    }
}
