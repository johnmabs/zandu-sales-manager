<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\Contract;

use RuntimeException;
use Zandu\SharedKernel\Identity\ProductId;

final class StockTransferStockUnavailable extends RuntimeException
{
    public function __construct(public readonly ProductId $productId)
    {
        parent::__construct(sprintf('Stock is insufficient for product "%s".', $productId->toString()));
    }
}
