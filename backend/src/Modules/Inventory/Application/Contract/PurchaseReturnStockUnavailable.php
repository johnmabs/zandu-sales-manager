<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\Contract;

use RuntimeException;
use Zandu\SharedKernel\Identity\ProductId;

final class PurchaseReturnStockUnavailable extends RuntimeException
{
    public function __construct(public readonly ProductId $productId)
    {
        parent::__construct(sprintf('Insufficient stock for returned product %s.', $productId->toString()));
    }
}
