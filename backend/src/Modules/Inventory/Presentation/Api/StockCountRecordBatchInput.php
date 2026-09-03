<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Presentation\Api;

final readonly class StockCountRecordBatchInput
{
    /** @param list<array{productId:string,countedQuantity:string,expectedLineVersion:int}> $entries */
    public function __construct(public array $entries) {}
}
