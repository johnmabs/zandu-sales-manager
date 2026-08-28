<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Domain;

use LogicException;
use Zandu\SharedKernel\Decimal\RoundingMode;
use Zandu\SharedKernel\Identity\{ProductId, ReturnSaleLineId, SaleLineId};
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class ReturnSaleLine
{
    private ?string $reason;
    private Quantity $baseReturnedQuantity;

    public function __construct(
        private ReturnSaleLineId $id,
        private SaleLine $originalLine,
        private ?SaleLineCostSnapshot $originalCostSnapshot,
        private Quantity $returnedQuantity,
        private bool $restock,
        ?string $reason,
    ) {
        if ($returnedQuantity->isZero() || $returnedQuantity->isNegative()) {
            throw SalesRuleViolation::with('RETURN_QUANTITY_INVALID', 'Return quantity must be greater than zero.');
        }
        if ($returnedQuantity->compareTo($originalLine->enteredQuantity()) > 0) {
            throw SalesRuleViolation::with('RETURN_QUANTITY_EXCEEDS_SOLD', 'Return quantity cannot exceed the sold quantity.');
        }
        if (null !== $originalCostSnapshot && !$originalCostSnapshot->saleLineId()->equals($originalLine->id())) {
            throw new LogicException('Return cost snapshot belongs to another sale line.');
        }

        $this->reason = self::normalizeReason($reason);
        $this->baseReturnedQuantity = $returnedQuantity->multiply(
            $originalLine->conversionFactorSnapshot()->value(),
            12,
            RoundingMode::HalfUp,
        );
    }

    public function id(): ReturnSaleLineId
    {
        return $this->id;
    }

    public function saleLineId(): SaleLineId
    {
        return $this->originalLine->id();
    }

    public function productId(): ProductId
    {
        return $this->originalLine->productId();
    }

    public function returnedQuantity(): Quantity
    {
        return $this->returnedQuantity;
    }

    public function baseReturnedQuantity(): Quantity
    {
        return $this->baseReturnedQuantity;
    }

    public function restock(): bool
    {
        return $this->restock;
    }

    public function reason(): ?string
    {
        return $this->reason;
    }

    public function originalLine(): SaleLine
    {
        return $this->originalLine;
    }

    public function originalCostSnapshot(): ?SaleLineCostSnapshot
    {
        return $this->originalCostSnapshot;
    }

    private static function normalizeReason(?string $reason): ?string
    {
        if (null === $reason) {
            return null;
        }

        $reason = trim($reason);
        if ('' === $reason) {
            return null;
        }
        if (mb_strlen($reason) > 255) {
            throw new LogicException('Return line reason cannot exceed 255 characters.');
        }

        return $reason;
    }
}
