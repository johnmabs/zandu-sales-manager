<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\FinalizeStockCount;

use Zandu\Modules\Inventory\Application\ReconcileStockCount\StockCountCostAssignment;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\StockCountId;

final readonly class FinalizeStockCount
{
    /** @param list<StockCountCostAssignment> $costAssignments */
    public function __construct(
        public StockCountId $stockCountId,
        public ActorContext $actorContext,
        public int $batchSize = 100,
        public array $costAssignments = [],
    ) {
        if ($batchSize < 1 || $batchSize > 500) {
            throw new \InvalidArgumentException('Stock count reconciliation batch size must be between 1 and 500.');
        }
    }
}
