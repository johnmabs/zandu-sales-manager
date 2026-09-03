<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\FinalizeStockCount;

use Zandu\Modules\Inventory\Application\BeginStockCountFinalization\{BeginStockCountFinalization, BeginStockCountFinalizationHandler};
use Zandu\Modules\Inventory\Application\CompleteStockCountFinalization\{CompleteStockCountFinalization, CompleteStockCountFinalizationHandler};
use Zandu\Modules\Inventory\Application\ReconcileStockCount\{ReconcileStockCountBatch, ReconcileStockCountBatchHandler};
use Zandu\Modules\Inventory\Domain\StockCount\{StockCount, StockCountRepository, StockCountStatus};
use Zandu\SharedKernel\Tenancy\TenantTransaction;

final readonly class FinalizeStockCountHandler
{
    public function __construct(
        private StockCountRepository $stockCounts,
        private TenantTransaction $transaction,
        private BeginStockCountFinalizationHandler $begin,
        private ReconcileStockCountBatchHandler $reconcile,
        private CompleteStockCountFinalizationHandler $complete,
    ) {}

    public function __invoke(FinalizeStockCount $command): StockCount
    {
        $status = $this->transaction->transactional(
            $command->actorContext->organizationId(),
            fn(): StockCountStatus => $this->stockCounts->get(
                $command->actorContext->organizationId(),
                $command->stockCountId,
            )->status(),
        );
        if (StockCountStatus::Completed === $status) {
            return ($this->complete)(new CompleteStockCountFinalization($command->stockCountId, $command->actorContext));
        }

        ($this->begin)(new BeginStockCountFinalization($command->stockCountId, $command->actorContext));
        do {
            $result = ($this->reconcile)(new ReconcileStockCountBatch(
                $command->stockCountId,
                $command->batchSize,
                $command->actorContext,
                $command->costAssignments,
            ));
        } while ($result->remainingCount > 0);

        return ($this->complete)(new CompleteStockCountFinalization($command->stockCountId, $command->actorContext));
    }
}
