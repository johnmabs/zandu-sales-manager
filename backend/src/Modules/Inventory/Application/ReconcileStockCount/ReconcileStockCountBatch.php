<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\ReconcileStockCount;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\{ProductId, StockCountId};

final readonly class ReconcileStockCountBatch
{
    /** @var array<string, StockCountCostAssignment> */
    private array $costAssignments;

    /** @param list<StockCountCostAssignment> $costAssignments */
    public function __construct(public StockCountId $stockCountId, public int $batchSize, public ActorContext $actorContext, array $costAssignments = [])
    {
        if ($batchSize < 1 || $batchSize > 500) {
            throw new \InvalidArgumentException('Stock count reconciliation batch size must be between 1 and 500.');
        }

        $indexed = [];
        foreach ($costAssignments as $assignment) {
            $productId = $assignment->productId->toString();
            if (isset($indexed[$productId])) {
                throw new \InvalidArgumentException('A product can have only one stock count cost assignment.');
            }
            $indexed[$productId] = $assignment;
        }
        $this->costAssignments = $indexed;
    }

    public function costAssignmentFor(ProductId $productId): ?StockCountCostAssignment
    {
        return $this->costAssignments[$productId->toString()] ?? null;
    }
}
