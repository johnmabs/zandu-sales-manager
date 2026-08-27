<?php

declare(strict_types=1);

namespace Zandu\Modules\InventoryCosting\Domain\ValuationMovement;

use DateTimeImmutable;
use DateTimeZone;
use Zandu\Modules\InventoryCosting\Domain\InventoryCostingRuleViolation;
use Zandu\SharedKernel\Identity\{OrganizationId, ProductId, StockId, StockMovementId, StockValuationId, StockValuationMovementId, StoreId};
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\Money\Money;
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class StockValuationMovement
{
    private function __construct(
        private StockValuationMovementId $id,
        private StockValuationId $stockValuationId,
        private OrganizationId $organizationId,
        private StoreId $storeId,
        private ProductId $productId,
        private StockId $stockId,
        private ?StockMovementId $stockMovementId,
        private StockValuationMovementType $type,
        private Quantity $quantity,
        private Money $unitCost,
        private Money $value,
        private Money $previousTotalValue,
        private Money $resultingTotalValue,
        private Money $previousAverageCost,
        private Money $resultingAverageCost,
        private StockValuationMovementSource $source,
        private DateTimeImmutable $occurredAt,
        private CorrelationId $correlationId,
    ) {
        self::assertPhysicalMovementLink($type, $stockMovementId);
        if ($type->sourceType() !== $source->type()) {
            throw InventoryCostingRuleViolation::with(
                'VALUATION_SOURCE_MISMATCH',
                'Valuation movement source does not match its type.',
            );
        }
        self::assertValues(
            $type,
            $quantity,
            $unitCost,
            $value,
            $previousTotalValue,
            $resultingTotalValue,
            $previousAverageCost,
            $resultingAverageCost,
        );
    }

    public static function record(
        StockValuationMovementId $id,
        StockValuationId $stockValuationId,
        OrganizationId $organizationId,
        StoreId $storeId,
        ProductId $productId,
        StockId $stockId,
        ?StockMovementId $stockMovementId,
        StockValuationMovementType $type,
        Quantity $quantity,
        Money $unitCost,
        Money $value,
        Money $previousTotalValue,
        Money $resultingTotalValue,
        Money $previousAverageCost,
        Money $resultingAverageCost,
        StockValuationMovementSource $source,
        DateTimeImmutable $occurredAt,
        CorrelationId $correlationId,
    ): self {
        return new self(
            $id,
            $stockValuationId,
            $organizationId,
            $storeId,
            $productId,
            $stockId,
            $stockMovementId,
            $type,
            $quantity,
            $unitCost,
            $value,
            $previousTotalValue,
            $resultingTotalValue,
            $previousAverageCost,
            $resultingAverageCost,
            $source,
            $occurredAt->setTimezone(new DateTimeZone('UTC')),
            $correlationId,
        );
    }

    public function id(): StockValuationMovementId
    {
        return $this->id;
    }

    public function stockValuationId(): StockValuationId
    {
        return $this->stockValuationId;
    }

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }

    public function storeId(): StoreId
    {
        return $this->storeId;
    }

    public function productId(): ProductId
    {
        return $this->productId;
    }

    public function stockId(): StockId
    {
        return $this->stockId;
    }

    public function stockMovementId(): ?StockMovementId
    {
        return $this->stockMovementId;
    }

    public function type(): StockValuationMovementType
    {
        return $this->type;
    }

    public function quantity(): Quantity
    {
        return $this->quantity;
    }

    public function unitCost(): Money
    {
        return $this->unitCost;
    }

    public function value(): Money
    {
        return $this->value;
    }

    public function previousTotalValue(): Money
    {
        return $this->previousTotalValue;
    }

    public function resultingTotalValue(): Money
    {
        return $this->resultingTotalValue;
    }

    public function previousAverageCost(): Money
    {
        return $this->previousAverageCost;
    }

    public function resultingAverageCost(): Money
    {
        return $this->resultingAverageCost;
    }

    public function source(): StockValuationMovementSource
    {
        return $this->source;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function correlationId(): CorrelationId
    {
        return $this->correlationId;
    }

    private static function assertPhysicalMovementLink(
        StockValuationMovementType $type,
        ?StockMovementId $stockMovementId,
    ): void {
        if ($type->requiresStockMovement() && null === $stockMovementId) {
            throw InventoryCostingRuleViolation::with(
                'VALUATION_STOCK_MOVEMENT_REQUIRED',
                'A physical stock movement is required for this valuation movement.',
            );
        }
        if (!$type->requiresStockMovement() && null !== $stockMovementId) {
            throw InventoryCostingRuleViolation::with(
                'VALUATION_STOCK_MOVEMENT_UNEXPECTED',
                'An opening valuation cannot reference a new physical stock movement.',
            );
        }
    }

    private static function assertValues(
        StockValuationMovementType $type,
        Quantity $quantity,
        Money $unitCost,
        Money $value,
        Money $previousTotalValue,
        Money $resultingTotalValue,
        Money $previousAverageCost,
        Money $resultingAverageCost,
    ): void {
        if (($quantity->isZero() && StockValuationMovementType::Opening !== $type) || $quantity->isNegative()) {
            throw InventoryCostingRuleViolation::with(
                'VALUATION_MOVEMENT_QUANTITY_INVALID',
                'Valuation movement quantity must be positive outside an opening movement.',
            );
        }

        if (StockValuationMovementType::Opening === $type
            && (!$previousTotalValue->amount()->isZero() || !$previousAverageCost->amount()->isZero())) {
            throw InventoryCostingRuleViolation::with(
                'VALUATION_MOVEMENT_INCONSISTENT',
                'An opening valuation must start from zero value.',
            );
        }
        if (StockValuationMovementType::Opening === $type
            && $quantity->isZero()
            && (!$unitCost->amount()->isZero() || !$value->amount()->isZero())) {
            throw InventoryCostingRuleViolation::with(
                'VALUATION_MOVEMENT_INCONSISTENT',
                'A zero quantity opening cannot assert an inventory cost.',
            );
        }

        $amounts = [$unitCost, $value, $previousTotalValue, $resultingTotalValue, $previousAverageCost, $resultingAverageCost];
        foreach ($amounts as $amount) {
            if ($amount->amount()->isNegative()) {
                throw InventoryCostingRuleViolation::with(
                    'VALUATION_MOVEMENT_VALUE_INVALID',
                    'Valuation movement values cannot be negative.',
                );
            }
            if (!$value->currency()->equals($amount->currency())) {
                throw InventoryCostingRuleViolation::with(
                    'VALUATION_CURRENCY_MISMATCH',
                    'Valuation movement values must use the same currency.',
                );
            }
        }

        $expectedTotal = $type->isIncrease()
            ? $previousTotalValue->add($value)
            : $previousTotalValue->subtract($value);
        if (!$expectedTotal->equals($resultingTotalValue)) {
            throw InventoryCostingRuleViolation::with(
                'VALUATION_MOVEMENT_INCONSISTENT',
                'Valuation movement totals are inconsistent.',
            );
        }
    }
}
