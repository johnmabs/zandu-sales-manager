<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Presentation\Api;

final readonly class StockCountFinalizationInput
{
    /** @param list<array{productId:string,manualUnitCost:string,reason:string}> $costAssignments */
    public function __construct(
        public int $batchSize = 100,
        public array $costAssignments = [],
    ) {}
}
