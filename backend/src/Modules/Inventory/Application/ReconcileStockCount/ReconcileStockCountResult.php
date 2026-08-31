<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\ReconcileStockCount;

final readonly class ReconcileStockCountResult
{
    public function __construct(public int $processedCount, public int $remainingCount) {}
}
