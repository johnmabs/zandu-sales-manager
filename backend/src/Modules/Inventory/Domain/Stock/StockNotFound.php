<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Domain\Stock;

use RuntimeException;
use Zandu\SharedKernel\Error\ResourceNotFound;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\StockId;
use Zandu\SharedKernel\Identity\StoreId;

final class StockNotFound extends RuntimeException implements ResourceNotFound
{
    public static function forPosition(StoreId $storeId, ProductId $productId): self
    {
        return new self(sprintf('Stock for store "%s" and product "%s" was not found.', $storeId->toString(), $productId->toString()));
    }

    public static function withId(StockId $stockId): self
    {
        return new self(sprintf('Stock "%s" was not found.', $stockId->toString()));
    }
}
