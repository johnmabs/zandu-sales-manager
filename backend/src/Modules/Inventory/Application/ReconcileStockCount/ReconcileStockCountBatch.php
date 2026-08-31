<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\ReconcileStockCount;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\StockCountId;

final readonly class ReconcileStockCountBatch
{
    public function __construct(public StockCountId $stockCountId, public int $batchSize, public ActorContext $actorContext)
    {
        if ($batchSize < 1 || $batchSize > 500) {
            throw new \InvalidArgumentException('Stock count reconciliation batch size must be between 1 and 500.');
        }
    }
}
