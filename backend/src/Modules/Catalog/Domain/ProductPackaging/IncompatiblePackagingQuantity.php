<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Domain\ProductPackaging;

use InvalidArgumentException;

final class IncompatiblePackagingQuantity extends InvalidArgumentException
{
    public static function notPositive(): self
    {
        return new self('Entered packaging quantity must be greater than zero.');
    }

    public static function belowMinimum(): self
    {
        return new self('Entered packaging quantity is below the minimum quantity.');
    }

    public static function exceedsPackagingPrecision(): self
    {
        return new self('Entered quantity exceeds product packaging precision.');
    }

    public static function violatesIncrement(): self
    {
        return new self('Entered packaging quantity must be an exact multiple of its quantity increment.');
    }

    public static function exceedsBasePrecision(): self
    {
        return new self('Converted quantity exceeds base unit precision.');
    }
}
