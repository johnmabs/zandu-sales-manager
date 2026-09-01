<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Domain\StockTransfer;

use RuntimeException;
use Zandu\SharedKernel\Error\ResourceNotFound;
use Zandu\SharedKernel\Identity\StockTransferId;

final class StockTransferNotFound extends RuntimeException implements ResourceNotFound
{
    public static function withId(StockTransferId $id): self
    {
        return new self(sprintf('Stock transfer "%s" was not found.', $id->toString()));
    }
}
