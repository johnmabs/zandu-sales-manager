<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\Contract;

use DateTimeImmutable;
use LogicException;
use Zandu\SharedKernel\Decimal\RoundingMode;
use Zandu\SharedKernel\Identity\{ProductId, StockId, StockMovementId};
use Zandu\SharedKernel\Money\Money;
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class CostedStockConsumption
{
    public function __construct(
        public ProductId $productId,
        public StockId $stockId,
        public StockMovementId $stockMovementId,
        public Quantity $quantity,
        public Money $unitCost,
        public Money $totalCost,
        public int $valuationVersion,
        public DateTimeImmutable $occurredAt,
    ) {
        if ($quantity->isZero() || $quantity->isNegative()) {
            throw new LogicException('Costed stock consumption quantity must be positive.');
        }
        if ($unitCost->amount()->isNegative() || $totalCost->amount()->isNegative()) {
            throw new LogicException('Costed stock consumption values cannot be negative.');
        }
        if (!$unitCost->currency()->equals($totalCost->currency())
            || !$unitCost->multiply($quantity->value(), 6, RoundingMode::HalfEven)->equals($totalCost)) {
            throw new LogicException('Costed stock consumption values are inconsistent.');
        }
        if ($valuationVersion < 1) {
            throw new LogicException('Costed stock consumption valuation version must be positive.');
        }
    }
}
