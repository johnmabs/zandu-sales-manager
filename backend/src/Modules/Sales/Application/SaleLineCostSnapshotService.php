<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application;

use Zandu\Modules\Inventory\Application\Contract\{CostedStockConsumption, StockConsumptionResult};
use Zandu\Modules\Sales\Domain\{Sale, SaleLineCostSnapshot, SaleLineCostSnapshotRepository, SalesRuleViolation};
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class SaleLineCostSnapshotService
{
    public function __construct(private SaleLineCostSnapshotRepository $snapshots) {}

    public function capture(Sale $sale, ?StockConsumptionResult $consumption): void
    {
        if (null === $consumption || $consumption->alreadyConsumed) {
            return;
        }
        if (!$consumption->saleId->equals($sale->id())) {
            $this->mismatch();
        }

        /** @var array<string, CostedStockConsumption> $costs */
        $costs = [];
        foreach ($consumption->costedItems as $cost) {
            $product = $cost->productId->toString();
            if (isset($costs[$product])) {
                $this->mismatch();
            }
            $costs[$product] = $cost;
        }

        /** @var array<string, Quantity> $capturedQuantities */
        $capturedQuantities = [];
        $pending = [];
        foreach ($sale->lines() as $line) {
            $product = $line->productId()->toString();
            if (!isset($costs[$product])) {
                continue;
            }
            $cost = $costs[$product];
            if ($cost->unitCost->currency()->code() !== $sale->currency()) {
                $this->mismatch();
            }
            $capturedQuantities[$product] = isset($capturedQuantities[$product])
                ? $capturedQuantities[$product]->add($line->baseQuantity())
                : $line->baseQuantity();
            $pending[] = SaleLineCostSnapshot::capture(
                $sale->organizationId(),
                $line->id(),
                $cost->stockId,
                $cost->stockMovementId,
                $line->baseQuantity(),
                $cost->unitCost,
                $cost->valuationVersion,
                $cost->occurredAt,
            );
        }

        foreach ($costs as $product => $cost) {
            if (!isset($capturedQuantities[$product]) || !$capturedQuantities[$product]->equals($cost->quantity)) {
                $this->mismatch();
            }
        }
        foreach ($pending as $snapshot) {
            $this->snapshots->append($snapshot);
        }
    }

    private function mismatch(): never
    {
        throw SalesRuleViolation::with(
            'SALE_COST_SNAPSHOT_MISMATCH',
            'Sale line cost snapshots do not match stock consumption.',
        );
    }
}
