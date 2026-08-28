<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Domain;

use DateTimeImmutable;
use DateTimeZone;
use LogicException;
use Zandu\SharedKernel\Decimal\RoundingMode;
use Zandu\SharedKernel\Identity\{OrganizationId, SaleLineId, StockId, StockMovementId};
use Zandu\SharedKernel\Money\Money;
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class SaleLineCostSnapshot
{
    private function __construct(
        private OrganizationId $organizationId,
        private SaleLineId $saleLineId,
        private StockId $stockId,
        private StockMovementId $stockMovementId,
        private Quantity $quantity,
        private Money $unitCost,
        private Money $totalCost,
        private int $valuationVersion,
        private DateTimeImmutable $occurredAt,
    ) {
        if ($quantity->isZero() || $quantity->isNegative()) {
            throw new LogicException('Sale line cost quantity must be positive.');
        }
        if ($unitCost->amount()->isNegative() || $totalCost->amount()->isNegative()) {
            throw new LogicException('Sale line costs cannot be negative.');
        }
        if (!$unitCost->currency()->equals($totalCost->currency())) {
            throw new LogicException('Sale line costs must use one currency.');
        }
        if (!$unitCost->multiply($quantity->value(), 6, RoundingMode::HalfEven)->equals($totalCost)) {
            throw new LogicException('Sale line total cost does not match quantity and unit cost.');
        }
        if ($valuationVersion < 1) {
            throw new LogicException('Sale line valuation version must be positive.');
        }
    }

    public static function capture(
        OrganizationId $organizationId,
        SaleLineId $saleLineId,
        StockId $stockId,
        StockMovementId $stockMovementId,
        Quantity $quantity,
        Money $unitCost,
        int $valuationVersion,
        DateTimeImmutable $occurredAt,
    ): self {
        $normalizedUnitCost = $unitCost->withScale(12, RoundingMode::HalfEven);

        return new self(
            $organizationId,
            $saleLineId,
            $stockId,
            $stockMovementId,
            $quantity,
            $normalizedUnitCost,
            $normalizedUnitCost->multiply($quantity->value(), 6, RoundingMode::HalfEven),
            $valuationVersion,
            $occurredAt->setTimezone(new DateTimeZone('UTC')),
        );
    }

    public static function reconstitute(
        OrganizationId $organizationId,
        SaleLineId $saleLineId,
        StockId $stockId,
        StockMovementId $stockMovementId,
        Quantity $quantity,
        Money $unitCost,
        Money $totalCost,
        int $valuationVersion,
        DateTimeImmutable $occurredAt,
    ): self {
        return new self($organizationId, $saleLineId, $stockId, $stockMovementId, $quantity, $unitCost, $totalCost, $valuationVersion, $occurredAt->setTimezone(new DateTimeZone('UTC')));
    }

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }
    public function saleLineId(): SaleLineId
    {
        return $this->saleLineId;
    }
    public function stockId(): StockId
    {
        return $this->stockId;
    }
    public function stockMovementId(): StockMovementId
    {
        return $this->stockMovementId;
    }
    public function quantity(): Quantity
    {
        return $this->quantity;
    }
    public function unitCost(): Money
    {
        return $this->unitCost;
    }
    public function totalCost(): Money
    {
        return $this->totalCost;
    }
    public function valuationVersion(): int
    {
        return $this->valuationVersion;
    }
    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
