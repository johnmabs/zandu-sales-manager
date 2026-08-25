<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Domain\ProductPackaging;

use InvalidArgumentException;
use RuntimeException;
use Zandu\SharedKernel\Decimal\RoundingMode;
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class ProductPackagingQuantityConverter
{
    public function toBaseQuantity(
        Quantity $enteredQuantity,
        ProductPackaging $packaging,
        int $baseUnitPrecision,
    ): Quantity {
        if ($baseUnitPrecision < 0 || $baseUnitPrecision > 12) {
            throw new InvalidArgumentException('Base unit precision must be between 0 and 12.');
        }
        if ($enteredQuantity->isZero() || $enteredQuantity->isNegative()) {
            throw IncompatiblePackagingQuantity::notPositive();
        }
        if (self::scale($enteredQuantity) > $packaging->precision()->value()) {
            throw IncompatiblePackagingQuantity::exceedsPackagingPrecision();
        }
        if ($enteredQuantity->compareTo($packaging->minimumQuantity()) < 0) {
            throw IncompatiblePackagingQuantity::belowMinimum();
        }

        try {
            $enteredQuantity->divide(
                $packaging->quantityIncrement()->value(),
                0,
                RoundingMode::Unnecessary,
            );
        } catch (RuntimeException) {
            throw IncompatiblePackagingQuantity::violatesIncrement();
        }

        $baseQuantity = new Quantity(
            $enteredQuantity->value()->multiply($packaging->conversionFactor()->value()),
        );
        if (self::scale($baseQuantity) > $baseUnitPrecision) {
            throw IncompatiblePackagingQuantity::exceedsBasePrecision();
        }

        return $baseQuantity;
    }

    private static function scale(Quantity $quantity): int
    {
        $fraction = strchr($quantity->toString(), '.');

        return false === $fraction ? 0 : strlen(rtrim(substr($fraction, 1), '0'));
    }
}
