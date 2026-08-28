<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\Contract;

use Zandu\SharedKernel\Identity\ReturnSaleId;

final readonly class StockRestockResult
{
    public const int CONTRACT_VERSION = 1;

    /** @param list<RestockedStockItem> $items */
    public function __construct(
        public ReturnSaleId $returnSaleId,
        public bool $alreadyRestocked,
        public array $items = [],
    ) {}
}
