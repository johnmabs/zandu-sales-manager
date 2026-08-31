<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\Contract;

use Zandu\SharedKernel\Money\Money;

final readonly class StockTransferStockResult
{
    /** @param array<string, array{unitCost: Money,totalValue: Money}> $costs keyed by ProductId */
    public function __construct(public int $processedCount, public array $costs) {}
}
