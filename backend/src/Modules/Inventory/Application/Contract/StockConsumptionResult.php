<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\Contract;

use Zandu\SharedKernel\Identity\SaleId;

final readonly class StockConsumptionResult
{
    public const int CONTRACT_VERSION = 3;

    /** @param list<CostedStockConsumption> $costedItems */
    public function __construct(
        public SaleId $saleId,
        public bool $alreadyConsumed = false,
        public array $costedItems = [],
    ) {}
}
