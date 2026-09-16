<?php

declare(strict_types=1);

namespace Zandu\Modules\InventoryCosting\Domain\Valuation;

use Zandu\Modules\InventoryCosting\Domain\InventoryCostingRuleViolation;
use Zandu\SharedKernel\Decimal\RoundingMode;
use Zandu\SharedKernel\Identity\{OrganizationId, ProductId, StockId, StockValuationId, StoreId};
use Zandu\SharedKernel\Money\{Currency, Money};
use Zandu\SharedKernel\Quantity\Quantity;
use Zandu\SharedKernel\Versioning\{TracksAggregateVersion, VersionedAggregate};

final class StockValuation implements VersionedAggregate
{
    use TracksAggregateVersion;

    private const int VALUE_SCALE = 6;
    private const int UNIT_COST_SCALE = 12;

    private function __construct(
        private readonly StockValuationId $id,
        private readonly OrganizationId $organizationId,
        private readonly StoreId $storeId,
        private readonly ProductId $productId,
        private readonly StockId $stockId,
        private Quantity $quantityOnHand,
        private Money $totalValue,
        private int $version,
    ) {
        $this->assertValidVersion();
        self::assertState($quantityOnHand, $totalValue);
    }

    public static function initialize(
        StockValuationId $id,
        OrganizationId $organizationId,
        StoreId $storeId,
        ProductId $productId,
        StockId $stockId,
        Quantity $quantityOnHand,
        Money $openingUnitCost,
        MovingWeightedAverageCalculator $calculator,
    ): self {
        if ($quantityOnHand->isNegative()) {
            throw InventoryCostingRuleViolation::with(
                'VALUATION_QUANTITY_INVALID',
                'Stock valuation quantity cannot be negative.',
            );
        }
        if ($openingUnitCost->amount()->isNegative()) {
            throw InventoryCostingRuleViolation::with(
                'VALUATION_UNIT_COST_NEGATIVE',
                'Inventory unit cost cannot be negative.',
            );
        }

        $zeroValue = $openingUnitCost
            ->subtract($openingUnitCost)
            ->withScale(self::VALUE_SCALE, RoundingMode::HalfEven);
        $totalValue = $quantityOnHand->isZero()
            ? $zeroValue
            : $calculator->add(
                $quantityOnHand->subtract($quantityOnHand),
                $zeroValue,
                $quantityOnHand,
                $openingUnitCost,
            )->resultingTotalValue;

        return new self(
            $id,
            $organizationId,
            $storeId,
            $productId,
            $stockId,
            $quantityOnHand,
            $totalValue,
            1,
        );
    }

    public static function reconstitute(
        StockValuationId $id,
        OrganizationId $organizationId,
        StoreId $storeId,
        ProductId $productId,
        StockId $stockId,
        Quantity $quantityOnHand,
        Money $totalValue,
        int $version,
    ): self {
        return new self($id, $organizationId, $storeId, $productId, $stockId, $quantityOnHand, $totalValue, $version);
    }

    public function receive(
        Quantity $quantity,
        Money $unitCost,
        MovingWeightedAverageCalculator $calculator,
    ): MovingWeightedAverageResult {
        $result = $calculator->add($this->quantityOnHand, $this->totalValue, $quantity, $unitCost);
        $this->apply($result);

        return $result;
    }

    public function issue(
        Quantity $quantity,
        MovingWeightedAverageCalculator $calculator,
    ): MovingWeightedAverageResult {
        $result = $calculator->remove($this->quantityOnHand, $this->totalValue, $quantity);
        $this->apply($result);

        return $result;
    }

    public function averageUnitCost(): Money
    {
        if ($this->quantityOnHand->isZero()) {
            return $this->totalValue->withScale(self::UNIT_COST_SCALE, RoundingMode::HalfEven);
        }

        return $this->totalValue->divide(
            $this->quantityOnHand->value(),
            self::UNIT_COST_SCALE,
            RoundingMode::HalfEven,
        );
    }

    public function id(): StockValuationId
    {
        return $this->id;
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

    public function quantityOnHand(): Quantity
    {
        return $this->quantityOnHand;
    }

    public function totalValue(): Money
    {
        return $this->totalValue;
    }

    public function currency(): Currency
    {
        return $this->totalValue->currency();
    }

    private function apply(MovingWeightedAverageResult $result): void
    {
        self::assertState($result->resultingQuantity, $result->resultingTotalValue);
        $this->quantityOnHand = $result->resultingQuantity;
        $this->totalValue = $result->resultingTotalValue;
        $this->advanceVersion();
    }

    private static function assertState(Quantity $quantity, Money $totalValue): void
    {
        if ($quantity->isNegative() || $totalValue->amount()->isNegative()) {
            throw InventoryCostingRuleViolation::with(
                'VALUATION_STATE_INVALID',
                'Valuation quantity and total value cannot be negative.',
            );
        }
        if ($quantity->isZero() && !$totalValue->amount()->isZero()) {
            throw InventoryCostingRuleViolation::with(
                'VALUATION_STATE_INVALID',
                'A zero valuation quantity must have a zero total value.',
            );
        }
    }
}
