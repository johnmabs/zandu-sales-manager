<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Domain\StockCount;

use RuntimeException;
use Zandu\SharedKernel\Error\ResourceNotFound;
use Zandu\SharedKernel\Identity\StockCountId;

final class StockCountNotFound extends RuntimeException implements ResourceNotFound
{
    public static function withId(StockCountId $id): self
    {
        return new self(sprintf('Stock count "%s" was not found.', $id->toString()));
    }
}
