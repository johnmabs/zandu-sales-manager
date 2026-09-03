<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Presentation\Api;

final readonly class StockCountRecordInput
{
    public function __construct(
        public string $productId,
        public string $countedQuantity,
        public int $expectedLineVersion,
    ) {}
}
