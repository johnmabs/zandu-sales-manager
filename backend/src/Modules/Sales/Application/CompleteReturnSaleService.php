<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application;

use DateTimeZone;
use LogicException;
use Zandu\Modules\Inventory\Application\Contract\{InventoryStockRestocker, RestockSaleReturn};
use Zandu\Modules\Organization\Application\Contract\StoreBusinessContextProvider;
use Zandu\Modules\Sales\Domain\{ReturnSale, ReturnSaleRepository, ReturnSaleStatus, Sale, SaleRepository, SaleStatus, SalesRuleViolation};
use Zandu\SharedKernel\Quantity\Quantity;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class CompleteReturnSaleService
{
    public function __construct(
        private TenantTransaction $transaction,
        private SaleRepository $sales,
        private ReturnSaleRepository $returns,
        private InventoryStockRestocker $inventory,
        private StoreBusinessContextProvider $stores,
        private Clock $clock,
    ) {}

    public function __invoke(CompleteReturnSale $command): ReturnSale
    {
        return $this->transaction->transactional($command->actor->organizationId(), function () use ($command): ReturnSale {
            $candidate = $this->returns->get($command->actor->organizationId(), $command->returnSaleId);
            $source = $this->sales->getForUpdate($command->actor->organizationId(), $candidate->saleId());
            $return = $this->returns->getForUpdate($command->actor->organizationId(), $command->returnSaleId);
            $this->assertSource($source, $return);
            $this->assertCumulativeQuantities($source, $return);

            $restockItems = [];
            foreach ($return->lines() as $line) {
                if (!$line->restock()) {
                    continue;
                }
                $cost = $line->originalCostSnapshot() ?? throw SalesRuleViolation::with(
                    'SALE_LINE_COST_SNAPSHOT_NOT_FOUND',
                    'The original sale line cost snapshot is required to restock a return.',
                );
                $restockItems[] = [
                    'productId' => $line->productId(),
                    'baseQuantity' => $line->baseReturnedQuantity(),
                    'originalUnitCost' => $cost->unitCost(),
                ];
            }
            if ([] !== $restockItems) {
                $this->inventory->restockSaleReturn(new RestockSaleReturn(
                    $return->organizationId(),
                    $return->storeId(),
                    $return->id(),
                    $restockItems,
                    $command->actor,
                ));
            }

            $now = $this->clock->now();
            $store = $this->stores->provide($return->organizationId(), $return->storeId());
            $businessDate = $now->setTimezone(new DateTimeZone($store->timeZone))->format('Y-m-d');
            $return->complete($command->actor, $now, $businessDate);
            $this->returns->save($return);

            return $return;
        });
    }

    private function assertSource(Sale $source, ReturnSale $return): void
    {
        if (SaleStatus::Completed !== $source->status()) {
            throw SalesRuleViolation::with('SALE_NOT_RETURNABLE', 'Only a completed sale can be returned.');
        }
        if (!$source->id()->equals($return->saleId()) || !$source->storeId()->equals($return->storeId())) {
            throw new LogicException('Return sale source does not match its original sale.');
        }
    }

    private function assertCumulativeQuantities(Sale $source, ReturnSale $candidate): void
    {
        /** @var array<string, Quantity> $returned */
        $returned = [];
        foreach ($this->returns->findBySale($source->organizationId(), $source->id()) as $existing) {
            if (ReturnSaleStatus::Completed !== $existing->status() || $existing->id()->equals($candidate->id())) {
                continue;
            }
            foreach ($existing->lines() as $line) {
                $key = $line->saleLineId()->toString();
                $returned[$key] = isset($returned[$key])
                    ? $returned[$key]->add($line->baseReturnedQuantity())
                    : $line->baseReturnedQuantity();
            }
        }

        foreach ($candidate->lines() as $line) {
            $key = $line->saleLineId()->toString();
            $cumulative = isset($returned[$key])
                ? $returned[$key]->add($line->baseReturnedQuantity())
                : $line->baseReturnedQuantity();
            if ($cumulative->compareTo($source->line($line->saleLineId())->baseQuantity()) > 0) {
                throw SalesRuleViolation::with('RETURN_QUANTITY_EXCEEDS_SOLD', 'Cumulative return quantity cannot exceed the sold quantity.');
            }
        }
    }
}
